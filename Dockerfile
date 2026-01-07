FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends qpdf \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY index.php /app/index.php

# Cloud Run expects the container to listen on $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} /app/index.php"]
