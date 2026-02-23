#!/bin/bash
# Script di sincronizzazione upload via FTP
# Uso: ./sync-uploads.sh [server] [username] [password]

SERVER=${1:-$FTP_SERVER}
USERNAME=${2:-$FTP_USERNAME}
PASSWORD=${3:-$FTP_PASSWORD}

if [ -z "$SERVER" ] || [ -z "$USERNAME" ] || [ -z "$PASSWORD" ]; then
    echo "Uso: ./sync-uploads.sh <server> <username> <password>"
    echo "Oppure imposta le variabili FTP_SERVER, FTP_USERNAME, FTP_PASSWORD"
    exit 1
fi

echo "Sincronizzazione upload su $SERVER..."

# Crea archivio degli upload
cd "$(dirname "$0")"
tar -czf uploads-backup.tar.gz assets/uploads/clienti assets/uploads/progetti assets/uploads/task_images 2>/dev/null || true

echo "Upload compressi in uploads-backup.tar.gz"
echo ""
echo "Per caricare manualmente:"
echo "1. Accedi a SiteGround File Manager"
echo "2. Vai in public_html/assets/uploads/"
echo "3. Carica la cartella clienti, progetti, task_images"
echo ""
echo "Oppure usa lftp:"
echo "lftp -u $USERNAME,$PASSWORD $SERVER -e \"mirror -R assets/uploads/ /public_html/assets/uploads/; quit\""
