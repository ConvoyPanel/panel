FROM alpine:3

RUN apk add --no-cache logrotate

COPY laravel-logrotate /etc/logrotate.d/laravel

CMD ["/bin/sh", "-c", "while true; do echo \"Starting logrotate cycle at $(date)...\"; /usr/sbin/logrotate -v /etc/logrotate.conf; echo \"Logrotate cycle finished at $(date).\"; sleep 3600; done"]
