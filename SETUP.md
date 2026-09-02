# Motibro Auto Booker — setup (Docker)

Laravel 13 + Docker dev környezet. Portok **nem ütköznek** budget2 / dinamicHP / NeighborHub / gmail-evaluator projektekkel.

## Port térkép

| Szolgáltatás | URL / port | Konténer |
|--------------|------------|----------|
| Web (Nginx) | http://localhost:8092 | `motibro-nginx` |
| MySQL | localhost:3313 | `motibro-mysql` |
| Redis | localhost:6383 | `motibro-redis` |
| Mailpit UI | http://localhost:8029 | `motibro-mailhog` |
| Mail SMTP | localhost:1031 | `motibro-mailhog` |
| Vite dev | http://localhost:5275 | `motibro-node` |

Docker network: `motibro` · DB: `motibro` / `motibro` / `motibro`

## 1. lépés — Laravel scaffold (ha még nincs)

```bash
cd ~/Projects/Motibro
composer create-project laravel/laravel:^13.0 _tmp
shopt -s dotglob 2>/dev/null || true
mv _tmp/* _tmp/.[!.]* . 2>/dev/null || true
rmdir _tmp
```

## 2. lépés — `.env`

A Laravel `.env` fájlban a DB/Redis/Mail/Vite blokkokat illeszd a `.env.docker.example` tartalmával.

## 3. lépés — Konténerek

```bash
cd ~/Projects/Motibro
docker compose up -d --build
```

Ellenőrzés:

```bash
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep motibro
```

## 4. lépés — Laravel alapok

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

## 5. lépés — Bejelentkezés

- http://localhost:8092 → átirányít `/login`-ra
- Két seedelt felhasználó (`UserSeeder`); publikus regisztráció **nincs**
- Jelszavak csak a seederben / első telepítéskor — élesben cseréld

## 6. lépés — Ellenőrzés

- http://localhost:8092 — Laravel welcome
- http://localhost:8029 — Mailpit
- `docker compose exec app node scripts/motibro-book.mjs --dry-run` — placeholder JSON

## Szolgáltatások

| Service | Feladat |
|---------|---------|
| app | PHP 8.4-FPM + Node 22 + Playwright Chromium |
| nginx | :8092 |
| mysql | MySQL 8 |
| redis | cache, session, lock |
| mailhog | Mailpit (SMTP + UI) |
| scheduler | `php artisan schedule:work` |
| node | Vite dev (:5275 → 5173) |

## Fájlstruktúra (Docker)

```
Motibro/
├── docker-compose.yml
├── Dockerfile
├── .env.docker.example
├── SETUP.md
├── scripts/
│   ├── package.json
│   └── motibro-book.mjs
└── docker/
    ├── entrypoint.sh
    ├── nginx/default.conf
    ├── php/php.ini
    └── mysql/init/
```

## Emlékeztetők

- Dockerben: `DB_HOST=mysql`, `REDIS_HOST=redis`, `MAIL_HOST=mailhog` — **ne** `127.0.0.1`
- `VITE_DEV_SERVER_URL=http://localhost:5275` — host gépi mapped port
- Playwright: `PLAYWRIGHT_BROWSERS_PATH=/ms-playwright` az app konténerben
- Részletes terv: Obsidian `MotiBroAutoBooker fejlesztési terv.md`
