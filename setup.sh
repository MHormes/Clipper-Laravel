#!/bin/bash

# Default to 'docker' profile if no argument is provided
PROFILE=${1:-local}

echo "🌟 Using Profile: .env.$PROFILE"

# 1. Create Volumes
echo "📦 Ensuring volumes exist..."
docker volume create clipper_db_data > /dev/null
docker volume create clipper_minio_data > /dev/null

# 2. Start Containers with the specific profile
# Passing APP_PROFILE to the shell makes it available to docker-compose.yml
echo "🚀 Starting containers..."
APP_PROFILE=$PROFILE docker compose up -d --build

# 3. Wait for MinIO
echo "⏳ Waiting for MinIO (10s)..."
sleep 10

# 4. Configure Bucket
echo "🪣 Configuring MinIO bucket..."
docker exec clipper_storage sh -c "
  mc alias set local http://localhost:9000 admin password && \
  mc mb local/clipper-ms || echo 'Bucket already exists' && \
  mc anonymous set download local/clipper-ms"

echo "✅ System is up on profile: $PROFILE"