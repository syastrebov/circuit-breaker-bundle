FROM php:8.4-cli AS base

RUN apt-get update && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
ENTRYPOINT []
CMD ["bash"]

# --- memcached ---
FROM base AS memcached
RUN apt-get update && apt-get install -y --no-install-recommends libmemcached-dev zlib1g-dev libssl-dev \
    && pecl install -f memcached-3.2.0 \
    && docker-php-ext-enable memcached \
    && rm -rf /var/lib/apt/lists/*

# --- pdo (mysql + pgsql) ---
FROM base AS pdo
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# --- redis ---
FROM base AS redis
RUN pecl install redis && docker-php-ext-enable redis

# --- full ---
FROM base AS full
RUN apt-get update && apt-get install -y --no-install-recommends \
        libmemcached-dev libpq-dev zlib1g-dev libssl-dev \
    && pecl install redis \
    && pecl install -f memcached-3.2.0 \
    && docker-php-ext-enable redis memcached \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*
