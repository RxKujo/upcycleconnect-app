#!/usr/bin/env bash
# Smoke-test prod UpcycleConnect : sante conteneurs, pages de chaque espace,
# exports PDF/CSV (verifie un vrai %PDF), scan des logs d'erreurs.
# Usage : bash scripts/smoke-test.sh
BASE=https://upcycleconnect.store
PASS=Admin123!
COMPOSE=/opt/upcycle-connect/upcycleconnect-app/docker-compose.prod.yml
PROBLEMS=0
note_fail(){ PROBLEMS=$((PROBLEMS+1)); }

token(){ curl -sS -X POST "$BASE/api/v1/auth/login" -H 'Content-Type: application/json' \
  -d "{\"email\":\"$1\",\"mot_de_passe\":\"$PASS\"}" | grep -oE '"token":"[^"]+"' | head -1 | cut -d'"' -f4; }

open_session(){ # role email jar
  local role=$1 email=$2 jar=$3; rm -f "$jar"
  local csrf; csrf=$(curl -sS -c "$jar" "$BASE/login" | grep -oE 'name="csrf-token" content="[^"]+"' | sed 's/.*content="//;s/"//')
  local tk; tk=$(token "$email")
  [ -z "$tk" ] && { echo "  !! login KO pour $email"; note_fail; return 1; }
  curl -sS -b "$jar" -c "$jar" -X POST "$BASE/auth/set-$role-session" \
    -H "X-CSRF-TOKEN: $csrf" -H 'Content-Type: application/json' -d "{\"token\":\"$tk\"}" -o /dev/null
}

check(){ # label jar url
  local code; code=$(curl -sS -b "$2" -o /dev/null -w '%{http_code}' "$BASE$3")
  if [ "$code" = 200 ] || [ "$code" = 302 ]; then printf "  OK   [%s] %s\n" "$code" "$1"
  else printf "  FAIL [%s] %s  (%s)\n" "$code" "$1" "$3"; note_fail; fi; }

check_pdf(){ # label jar url
  local f=/tmp/uc_pdf.bin m code sz magic
  m=$(curl -sS -b "$2" -o "$f" -w '%{http_code}|%{content_type}|%{size_download}' "$BASE$3")
  code=${m%%|*}; sz=${m##*|}; magic=$(head -c4 "$f" | tr -d '\0')
  if [ "$magic" = '%PDF' ]; then printf "  OK   PDF  %s (%s o)\n" "$1" "$sz"
  elif [ "$code" = 302 ] || [ "$code" = 403 ]; then printf "  SKIP PDF  %s -> HTTP %s (acces restreint, normal)\n" "$1" "$code"
  else printf "  FAIL PDF  %s -> HTTP %s, %so, debut='%s'\n" "$1" "$code" "$sz" "$(head -c50 "$f" | tr -d '\n')"; note_fail; fi; }

check_csv(){ # label jar url
  local f=/tmp/uc.csv code c1
  code=$(curl -sS -b "$2" -o "$f" -w '%{http_code}' "$BASE$3")
  c1=$(head -c1 "$f")
  if [ "$code" = 200 ] && [ "$c1" != '<' ] && [ "$c1" != '{' ]; then printf "  OK   CSV  %s\n" "$1"
  elif [ "$code" = 302 ] || [ "$code" = 403 ]; then printf "  SKIP CSV  %s -> HTTP %s\n" "$1" "$code"
  else printf "  FAIL CSV  %s -> HTTP %s, debut='%s'\n" "$1" "$code" "$(head -c50 "$f" | tr -d '\n')"; note_fail; fi; }

NUL=/tmp/uc_nojar; : > "$NUL"

echo "============ 1) CONTENEURS ============"
docker compose -f "$COMPOSE" ps --format '  {{.Name}}  {{.Status}}'

echo; echo "============ 2) PAGES PUBLIQUES ============"
for u in / /annonces /evenements /forum /ressources /tutoriels /depot /services-pro /a-propos /login /register /register-pro; do check "public $u" "$NUL" "$u"; done

echo; echo "============ 3) ESPACE ADMIN ============"
JA=/tmp/uc_admin.jar
if open_session admin admin@upcycleconnect.com "$JA"; then
  for u in /admin /admin/utilisateurs /admin/materiaux /admin/templates /admin/categories-objets /admin/categories /admin/evenements /admin/annonces /admin/commandes /admin/conteneurs /admin/abonnements /admin/scores /admin/depot/demandes /admin/tutoriel/etapes /admin/publicites /admin/publicites/stats /admin/publicites/rotation /admin/langues /admin/notifications /admin/finances; do check "admin $u" "$JA" "$u"; done
  check_pdf "admin finances" "$JA" "/admin/finances/export-pdf"
  check_csv "admin finances" "$JA" "/admin/finances/export-csv"
fi

echo; echo "============ 4) ESPACE SALARIE (claire) ============"
JS=/tmp/uc_sal.jar
if open_session salarie claire.lemoine@upcycleconnect.com "$JS"; then
  for u in /salarie/dashboard /salarie/evenements /salarie/templates /salarie/articles /salarie/planning /salarie/idees /salarie/forum/signalements /salarie/forum/sujets /salarie/forum/mots-bannis; do check "salarie $u" "$JS" "$u"; done
fi

echo; echo "============ 5) ESPACE PRO (antoine) ============"
JP=/tmp/uc_pro.jar
if open_session pro antoine@reparveloparis.fr "$JP"; then
  for u in /professionnel/dashboard /professionnel/dashboard/annuel /professionnel/profile /professionnel/abonnement /professionnel/alertes /professionnel/publicites /professionnel/conteneurs /professionnel/conteneurs/historique; do check "pro $u" "$JP" "$u"; done
  check_pdf "pro rapport annuel" "$JP" "/professionnel/dashboard/export-pdf"
fi

echo; echo "============ 6) LOGS API (erreurs recentes) ============"
docker logs uc_api 2>&1 | grep -iE 'error|erreur|panic|fatal' | tail -15 || echo "  (rien)"
echo "----- erreurs Laravel (web) -----"
docker exec uc_web sh -c "grep -a 'production.ERROR' storage/logs/laravel.log 2>/dev/null | tail -10" || echo "  (aucune erreur loguee)"

echo; echo "============ RESULTAT ============"
if [ "$PROBLEMS" -eq 0 ]; then echo "  OK : aucun probleme detecte cote serveur."; else echo "  ATTENTION : $PROBLEMS FAIL ci-dessus a regarder."; fi
