#!/bin/bash

ENV_FILE=".env.production"

if [ -f "$ENV_FILE" ]; then
    echo "reading configuration from $ENV_FILE..."
    # Export variables, ignoring comments and empty lines
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "❌ Error: $ENV_FILE not found!"
    exit 1
fi

# Configuration
BACKUP_DIR="./backups/$(date +%Y-%m-%d_%H-%M-%S)"
DB_CONTAINER="clipper_postgres_prod"
S3_CONTAINER="clipper_storage_prod"
DB_NAME="${DB_DATABASE}"
DB_USER="${DB_USERNAME}"

mkdir -p "$BACKUP_DIR/csv"
mkdir -p "$BACKUP_DIR/storage"

echo "📂 Starting backup to $BACKUP_DIR..."

# --- 1. Export Postgres Tables to CSV ---
echo "🐘 Exporting Database tables to CSV..."
TABLES=$(docker exec $DB_CONTAINER psql -U $DB_USER -d $DB_NAME -t -c "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name != 'migrations';")

for TABLE in $TABLES; do
    echo "   -> Exporting $TABLE..."
    # We use psql's COPY command to output CSV format directly to stdout, then save to file
    docker exec $DB_CONTAINER psql -U $DB_USER -d $DB_NAME -c "COPY $TABLE TO STDOUT WITH (FORMAT CSV, HEADER);" > "$BACKUP_DIR/csv/$TABLE.csv"
done

# --- 2. Download MinIO Bucket ---
echo "📦 Downloading MinIO bucket (clipper-ms)..."
# We use 'mc mirror' to sync the container's bucket to our local backup folder
docker exec $S3_CONTAINER sh -c "mc alias set local http://localhost:9000 ${AWS_ACCESS_KEY_ID} ${AWS_SECRET_ACCESS_KEY} > /dev/null && mc mirror local/clipper-ms /tmp/backup_mirror"
# Copy from container to host
docker cp $S3_CONTAINER:/tmp/backup_mirror/. "$BACKUP_DIR/storage/"
# Clean up temp files in container
docker exec $S3_CONTAINER rm -rf /tmp/backup_mirror

echo "✅ Backup complete!"
echo "📍 CSVs are in: $BACKUP_DIR/csv"
echo "📍 Files are in: $BACKUP_DIR/storage"