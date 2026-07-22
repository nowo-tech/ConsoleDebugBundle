#!/bin/sh
set -e

# FRANKENPHP_MODE: classic | worker. Default: classic (dev / first-boot safe).
# Set via .env / Compose only — not baked into the image ENV.
MODE="${FRANKENPHP_MODE:-classic}"
case "$MODE" in
	classic)
		cp /etc/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile
		;;
	worker)
		# Image default Caddyfile is worker (Dockerfile COPY). After changing
		# FRANKENPHP_MODE, recreate the container (`docker compose up -d`).
		;;
	*)
		echo "Unknown FRANKENPHP_MODE=$MODE (expected classic|worker)" >&2
		exit 1
		;;
esac
echo "FrankenPHP mode: $MODE"

mkdir -p /app/var/cache /app/var/log
chmod -R 777 /app/var
exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
