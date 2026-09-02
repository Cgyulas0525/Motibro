FROM php:8.4-fpm

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Rendszer csomagok + fejlécek
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    zip \
    unzip \
    libxml2-dev \
    libzip-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    pkg-config \
    $PHPIZE_DEPS \
 && rm -rf /var/lib/apt/lists/*

# PHP extensionök
RUN docker-php-ext-install -j"$(nproc)" zip pdo_mysql mbstring exif pcntl bcmath
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd

# Redis extension (phpredis)
RUN pecl install redis && docker-php-ext-enable redis

# Node.js 22 + Playwright (Motibro booking script)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
 && apt-get install -y --no-install-recommends nodejs \
 && rm -rf /var/lib/apt/lists/*

ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
ENV NODE_BINARY=node

WORKDIR /var/www/html

# Playwright Chromium — csak ha van scripts/package.json (build után is telepíthető entrypoint-ból)
COPY scripts/package.json /var/www/html/scripts/package.json
RUN cd /var/www/html/scripts && npm install && npx playwright install chromium --with-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN git config --system --add safe.directory /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
