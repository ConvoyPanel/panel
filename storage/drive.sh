cd /var/www/html
D=$(psql -U db -d db -tAc "select max(id) from deployments where server_id=11")
echo "deployment $D"
for i in $(seq 1 12); do
  timeout 240 php artisan queue:work --stop-when-empty --tries=1 --timeout=220 >/dev/null 2>&1
  st=$(psql -U db -d db -tAc "select string_agg(status, ',' order by sequence) from deployment_steps where deployment_id=$D")
  echo "pass $i: $st"
  case "$st" in
    *pending*|*running*) sleep 8 ;;
    *) break ;;
  esac
done
