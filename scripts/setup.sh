#!/bin/bash

# Move to the root of the project
cd "$(dirname "$0")/.."

# 1. Bepaal het profiel (standaard 'local')
PROFILE=${1:-local}
ENV_SOURCE=".env.$PROFILE"
COMPOSE_SOURCE="docker-compose-$PROFILE.yml"

echo "🌟 Gebruik profiel: $PROFILE"

# 2. Validatie: Bestaat het bronbestand?
if [ ! -f "$ENV_SOURCE" ]; then
    echo "❌ Fout: $ENV_SOURCE niet gevonden!"
    exit 1
fi

# 3. Bestanden klaarmaken (ALLEEN voor productie/server)
# Als het profile NIET local is, overschrijven we .env en docker-compose.yml voor Dockge

if [ "$PROFILE" != "local" ]; then
    echo "📝 Server modus: Bestanden synchroniseren naar .env en docker-compose.yml voor Dockge..."
    cp "$ENV_SOURCE" .env
    cp "$COMPOSE_SOURCE" docker-compose.yml
    COMPOSE_FILE="docker-compose.yml"
    ENV_FILE=".env"
else
    echo "🏠 Lokale modus: Ik raak je standaard .env en docker-compose.yml NIET aan."   
    # We gebruiken de bronbestanden direct zonder te kopiëren
    COMPOSE_FILE="$COMPOSE_SOURCE"
    ENV_FILE="$ENV_SOURCE"

fi

toggle_maintenance() {
    local action=$1
    if [ "$PROFILE" == "production" ] && [ ! -z "$CLOUDFLARE_ZONE_ID" ]; then
        
        local TARGET_DOMAIN=$(echo "$APP_URL" | sed -e 's|^[^/]*//||' -e 's|/.*$||')
        local WORKER_NAME="${CLOUDFLARE_WORKER_NAME:-maintenance-page}"

        if [ "$action" == "on" ]; then
            echo "🚧 Enabling Maintenance Mode for $TARGET_DOMAIN using worker [$WORKER_NAME]..."
            
            # We use jq to check the success field directly
            RESPONSE=$(curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/workers/routes" \
                 -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
                 -H "Content-Type: application/json" \
                 --data "{\"pattern\":\"$TARGET_DOMAIN/*\",\"script\":\"$WORKER_NAME\"}")

            SUCCESS=$(echo "$RESPONSE" | jq -r '.success')

            if [ "$SUCCESS" == "true" ]; then
                echo "✅ Maintenance Route active in Cloudflare."
            else
                echo "❌ Cloudflare Error: $RESPONSE"
            fi
        else
            echo "🟢 Disabling Maintenance Mode..."
            
            # Find the ID of the route we created
            ROUTE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/workers/routes" \
                        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | \
                        jq -r ".result[] | select(.pattern==\"$TARGET_DOMAIN/*\") | .id")
            
            if [ ! -z "$ROUTE_ID" ] && [ "$ROUTE_ID" != "null" ]; then
                curl -s -X DELETE "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/workers/routes/$ROUTE_ID" \
                     -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" > /dev/null
                echo "✅ Maintenance Route removed. Site is LIVE."
            else
                echo "⚠️ No active maintenance route found for $TARGET_DOMAIN."
            fi
        fi
    fi
}

# 4. Omgevingsvariabelen laden (nodig voor de volumes en MinIO checks in dit script)
export $(grep -v '^#' "$ENV_FILE" | xargs)

toggle_maintenance "on"


echo "🛑 Stop & verwijder containers..."
docker compose -f "$COMPOSE_FILE" down

# 5. Volumes aanmaken
echo "📦 Volumes controleren..."
if [ "$PROFILE" = "local" ]; then
    docker volume create clipper_db_data > /dev/null
    docker volume create clipper_minio_data > /dev/null
else
    docker volume create clipper_db_data_prod > /dev/null
    docker volume create clipper_minio_data_prod > /dev/null
fi

# 6. Start Containers
echo "🚀 Bouwen en opstarten..."
APP_PROFILE=$PROFILE docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --build

# 3. Permissies Fixen (Essentieel na 'up')
echo "🔒 Rechten herstellen voor Laravel storage..."
# We zoeken de container die op dat moment draait
CONTAINER_APP=$(docker ps --format "{{.Names}}" | grep "_app")
if [ ! -z "$CONTAINER_APP" ]; then
    docker exec -u root "$CONTAINER_APP" chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    docker exec -u root "$CONTAINER_APP" chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    docker exec -u root "$CONTAINER_APP" chown -R www-data:www-data /var/www/html/public/build
    echo "✅ Rechten gefixt in $CONTAINER_APP"
else
    echo "⚠️ Waarschuwing: App container niet gevonden voor permissie-fix."
fi

# 7. Wachten op MinIO
echo "⏳ Wachten op MinIO (10s)..."
sleep 10

# 8. Configure Bucket
echo "🪣 MinIO bucket configureren..."
if [ "$PROFILE" = "local" ]; then
    CONTAINER_NAME="clipper_storage"
else
    CONTAINER_NAME="clipper_storage_prod"
fi

docker exec $CONTAINER_NAME sh -c "
    mc alias set local http://localhost:9000 ${AWS_ACCESS_KEY_ID} ${AWS_SECRET_ACCESS_KEY} && \
    mc mb local/clipper-ms || echo 'Bucket bestaat al' && \
    mc anonymous set download local/clipper-ms"

toggle_maintenance "off"

echo "✅ Systeem is up op profiel: $PROFILE" 