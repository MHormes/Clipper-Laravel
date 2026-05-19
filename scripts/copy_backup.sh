#!/bin/bash

REMOTE_HOST="clipper"
REMOTE_BACKUPS_DIR="/home/clipper/clipper-ms/Clipper-Laravel/backups"
LOCAL_DEST="$HOME/Desktop/ClipperBackups"

echo "🔍 Checking latest backup on $REMOTE_HOST..."
FOLDER_NAME=$(ssh "$REMOTE_HOST" "ls -1 $REMOTE_BACKUPS_DIR | grep -E '^[0-9]{4}-' | sort -r | head -1 | tr -d '\r\n'")

if [ -z "$FOLDER_NAME" ]; then
    echo "❌ No backups found on $REMOTE_HOST at $REMOTE_BACKUPS_DIR"
    exit 1
fi

echo "📦 Latest backup: '$FOLDER_NAME'"
echo "🔗 Full remote path: '$REMOTE_BACKUPS_DIR/$FOLDER_NAME'"
mkdir -p "$LOCAL_DEST"

read -p "Proceed with copy? (y/n) " CONFIRM
[ "$CONFIRM" != "y" ] && exit 0

echo "📥 Copying $FOLDER_NAME..."
scp -r "$REMOTE_HOST:$REMOTE_BACKUPS_DIR/$FOLDER_NAME" "$LOCAL_DEST/"

echo "📋 Copying log file(s)..."
ssh "$REMOTE_HOST" "ls $REMOTE_BACKUPS_DIR/*.log 2>/dev/null | tr -d '\r'" | while read -r LOG; do
    [ -n "$LOG" ] && scp "$REMOTE_HOST:$LOG" "$LOCAL_DEST/"
done

echo "✅ Done. Backup saved to $LOCAL_DEST/$FOLDER_NAME"
