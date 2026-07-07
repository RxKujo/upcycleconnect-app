# Comptes de test

Mot de passe pour **tous** les comptes : `Admin123!`

URL de connexion : http://localhost:8000/login

## Services & consoles (dev)

| Service | URL | Identifiants |
|---|---|---|
| Application | http://localhost:8000 | comptes ci-dessous |
| API (Go) | http://localhost:8888 | — |
| **Console MinIO** (bucket S3) | http://localhost:9001 | `minioadmin` / `minioadmin` |
| Emails (Mailpit) | http://localhost:8025 | — (aucune auth) |
| Base de données (phpMyAdmin) | http://localhost:8081 | `DB_USERNAME` / `DB_PASSWORD` du `.env` |
| GLPI (ticketing) | http://localhost:8082 | assistant d'install au 1er lancement |

> Le bucket S3 s'appelle **`upcycleconnect`** ; photos dans les dossiers `photos/`, `conteneurs/`, `materiels/`, `materiaux/`.
> MinIO démarre avec la stack dev ; GLPI seulement avec le profil : `docker compose -f docker-compose.dev.yml --profile glpi up -d`.

## Admin

- admin@upcycleconnect.com — Admin UpcycleConnect

## Salariés

- claire.lemoine@upcycleconnect.com — Claire Lemoine (site Paris 11)
- nicolas.faure@upcycleconnect.com — Nicolas Faure (site Lyon Croix-Rousse)

## Professionnels / Artisans

- antoine@reparveloparis.fr — Antoine Leclerc (RéparVélo Paris)
- marie@atelier-couture.fr — Marie Moreau (Atelier Couture Lyon)
- thomas@meublerecup.fr — Thomas Garnier (Meuble Récup Sud)

## Particuliers

- sophie.martin@test.com — Sophie Martin (Paris)
- lucas.dubois@test.com — Lucas Dubois (Lyon)
- emma.bernard@test.com — Emma Bernard (Marseille, certifiée, score 250)
- julien.petit@test.com — Julien Petit (Paris)
- camille.rousseau@test.com — Camille Rousseau (Toulouse)
