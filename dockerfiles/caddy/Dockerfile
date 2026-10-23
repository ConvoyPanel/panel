# Pinned to an exact version: the 2.6 tag stopped at 2.6.4 in February 2023, so
# the one container that faces the internet went years without security fixes.
FROM caddy:2.11.7-alpine

ARG APP_ENV
ARG APP_URL

COPY ./Caddyfile-* /etc/caddy/

RUN if [ $APP_URL = "http://localhost" ] || [ $APP_URL = "http://127.0.0.1" ] || [ $APP_URL = "https://localhost" ] || [ $APP_URL = "https://127.0.0.1" ]; then \
                cp /etc/caddy/Caddyfile-development /etc/caddy/Caddyfile; \
        elif [ $APP_ENV = "production" ]; then \
                cp /etc/caddy/Caddyfile-production /etc/caddy/Caddyfile; \
        else \
                cp /etc/caddy/Caddyfile-development /etc/caddy/Caddyfile; \
        fi