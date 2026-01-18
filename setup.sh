#!/bin/bash

# Default to 'docker' profile if no argument is provided
PROFILE=${1:-local}

ENV_FILE=".env.$PROFILE"

if [ -f "$ENV_FILE" ]; then
    echo "reading configuration from $ENV_FILE..."
    # Export variables, ignoring comments and empty lines
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "❌ Error: $ENV_FILE not found!"
    exit 1
fi

echo "Stopping & removing containers..."
docker compose -f docker-compose-$PROFILE.yml down

echo "🌟 Using Profile: .env.$PROFILE"

# 1. Create Volumes
echo "📦 Ensuring volumes exist..."

if [ $PROFILE = "local" ]; then
  docker volume create clipper_db_data > /dev/null
docker volume create clipper_minio_data > /dev/null
else
  docker volume create clipper_db_data_prod > /dev/null
docker volume create clipper_minio_data_prod > /dev/null
fi

# 2. Start Containers with the specific profile
# Passing APP_PROFILE to the shell makes it available to docker-compose.yml
echo "🚀 Starting containers..."
APP_PROFILE=$PROFILE docker compose -f docker-compose-$PROFILE.yml --env-file .env.$PROFILE up -d --build


# 3. Wait for MinIO
echo "⏳ Waiting for MinIO (10s)..."
sleep 10

# 4. Configure Bucket
echo "🪣 Configuring MinIO bucket..."


if [ $PROFILE = "local" ]; then
  docker exec clipper_storage sh -c "
    mc alias set local http://localhost:9000 ${AWS_ACCESS_KEY_ID} ${AWS_SECRET_ACCESS_KEY} && \
    mc mb local/clipper-ms || echo 'Bucket already exists' && \
    mc anonymous set download local/clipper-ms"
else
  docker exec clipper_storage_prod sh -c "
    mc alias set local http://localhost:9000 ${AWS_ACCESS_KEY_ID} ${AWS_SECRET_ACCESS_KEY} && \
    mc mb local/clipper-ms || echo 'Bucket already exists' && \
    mc anonymous set download local/clipper-ms"
fi

echo "✅ System is up on profile: $PROFILE"