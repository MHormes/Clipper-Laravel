#!/bin/bash

# 1. Configuratie laden
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$BASE_DIR/.env.production"

if [ -f "$ENV_FILE" ]; then
    echo "📖 Configuratie lezen uit $ENV_FILE..."
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "❌ Fout: $ENV_FILE niet gevonden!"
    exit 1
fi

# 2. Instellingen
BACKUP_BASE_DIR="/home/clipper/clipper-ms/Clipper-Laravel/backups"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="$BACKUP_BASE_DIR/$TIMESTAMP"
DB_CONTAINER="clipper_postgres_prod"
S3_CONTAINER="clipper_storage_prod"
DB_NAME="${DB_DATABASE}"
DB_USER="${DB_USERNAME}"

mkdir -p "$BACKUP_DIR/csv"
mkdir -p "$BACKUP_DIR/storage"

echo "📂 Starten van backup naar $BACKUP_DIR..."

# --- 1. Database naar CSV exporteren ---
echo "🐘 Database tabellen exporteren naar CSV..."
TABLES=$(docker exec $DB_CONTAINER psql -U $DB_USER -d $DB_NAME -t -c "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name != 'migrations';")

for TABLE in $TABLES; do
    echo "   -> Exporteren van $TABLE..."
    docker exec $DB_CONTAINER psql -U $DB_USER -d $DB_NAME -c "COPY $TABLE TO STDOUT WITH (FORMAT CSV, HEADER);" > "$BACKUP_DIR/csv/$TABLE.csv"
done

# --- 2. MinIO Bucket downloaden ---
echo "📦 MinIO bucket downloaden (clipper-ms)..."
docker exec $S3_CONTAINER sh -c "mc alias set local http://localhost:9000 ${AWS_ACCESS_KEY_ID} ${AWS_SECRET_ACCESS_KEY} > /dev/null && mc mirror local/clipper-ms /tmp/backup_mirror"
docker cp $S3_CONTAINER:/tmp/backup_mirror/. "$BACKUP_DIR/storage/"
docker exec $S3_CONTAINER rm -rf /tmp/backup_mirror

# --- 3. Controle en Opschonen ---
# We controleren of de backup map inderdaad bestanden bevat
if [ "$(ls -A $BACKUP_DIR)" ]; then
    echo "✅ Backup voltooid op $(date)"
    echo "📍 CSV's staan in: $BACKUP_DIR/csv"
    echo "📍 Bestanden staan in: $BACKUP_DIR/storage"

    # Navigeer naar de hoofdmap voor opschonen
    cd "$BACKUP_BASE_DIR" || exit
    
    # Behoud alleen de 3 nieuwste mappen
    echo "🧹 Controleren op oude backups (maximaal 3 behouden)..."
    ls -dt */ | tail -n +4 | xargs -I {} rm -rf "{}"
    echo "✨ Systeem is weer up-to-date."
else
    echo "❌ Backup mislukt! De backup map is leeg. Geen oude backups verwijderd."
    exit 1
fi