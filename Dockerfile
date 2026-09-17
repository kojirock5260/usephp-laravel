# Sandbox image: PHP 8.5 CLI + Composer, serving the Testbench workbench.
FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-interaction --prefer-dist

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["vendor/bin/testbench", "serve", "--host=0.0.0.0", "--port=8000"]
