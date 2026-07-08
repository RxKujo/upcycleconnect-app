#!/usr/bin/env bash
# Migre les fichiers déjà présents dans web/public/uploads vers le bucket S3
# (MinIO en dev), en conservant EXACTEMENT les mêmes clés :
#   photos/…, conteneurs/…, materiaux/…
# Les chemins stockés en base (url_photo, photo_profil_url) restent donc valides
# tels quels — seule la base de lecture (MEDIA_URL) change. Aucune requête SQL.
#
# Usage (dev, pile docker-compose.dev.yml démarrée) :
#   bash scripts/migrate-uploads-to-s3.sh
#
# Variables (valeurs par défaut = dev) :
#   MINIO_BUCKET, MINIO_ROOT_USER, MINIO_ROOT_PASSWORD, DOCKER_NETWORK, MINIO_ENDPOINT
set -euo pipefail

BUCKET="${MINIO_BUCKET:-upcycleconnect}"
USER="${MINIO_ROOT_USER:-minioadmin}"
PASS="${MINIO_ROOT_PASSWORD:-minioadmin}"
NETWORK="${DOCKER_NETWORK:-uc_network}"
ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UPLOADS="$ROOT/web/public/uploads"

if [ ! -d "$UPLOADS" ]; then
  echo "Rien à migrer : $UPLOADS introuvable."
  exit 0
fi

echo "Migration de $UPLOADS -> bucket '$BUCKET' ($ENDPOINT) via le réseau '$NETWORK'..."

# Windows / Git Bash : convertir le chemin hôte en forme mixte (C:/...) et
# désactiver la conversion de chemins MSYS qui casserait le montage -v.
if command -v cygpath >/dev/null 2>&1; then
  UPLOADS="$(cygpath -m "$UPLOADS")"
  export MSYS_NO_PATHCONV=1
fi

docker run --rm --network "$NETWORK" \
  -v "$UPLOADS:/data:ro" \
  --entrypoint sh minio/mc:latest -c "
    until mc alias set local $ENDPOINT $USER $PASS; do echo 'attente MinIO...'; sleep 2; done &&
    mc mb --ignore-existing local/$BUCKET &&
    mc anonymous set download local/$BUCKET &&
    mc mirror --overwrite /data local/$BUCKET &&
    echo 'Migration terminée.'
  "
