#!/bin/bash

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

# 4. Omgevingsvariabelen laden (nodig voor de volumes en MinIO checks in dit script)
export $(grep -v '^#' "$ENV_FILE" | xargs)

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

echo "✅ Systeem is up op profiel: $PROFILE"