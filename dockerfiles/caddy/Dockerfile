FROM caddy:2.6.1-alpine

ARG APP_ENV

COPY ./Caddyfile-* /etc/caddy/

RUN if [ $APP_ENV = "production" ]; then \
                cp /etc/caddy/Caddyfile-production /etc/caddy/Caddyfile; \
        else \
                cp /etc/caddy/Caddyfile-development /etc/caddy/Caddyfile; \
        fi
