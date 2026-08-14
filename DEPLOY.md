# StupnikBike Admin Production Deploy

Domain:

```txt
admin.stupnik.bike
```

Production URL:

```txt
https://admin.stupnik.bike/admin/login
```

## 1. DNS

Na DNS-u domene `stupnik.bike` dodaj:

```txt
Type: A
Name: admin
Value: SERVER_IP_ADRESA
TTL: Auto
```

Na serveru moraju biti otvoreni portovi:

```txt
80
443
```

## 2. Server Folder

Primjer foldera na serveru:

```bash
mkdir -p /opt/stupnikbike
cd /opt/stupnikbike
```

Prebaci LaravelAdmin projekt u:

```txt
/opt/stupnikbike/LaravelAdmin
```

## 3. Environment

U folderu `LaravelAdmin` napravi produkcijski env:

```bash
cp .env.production.example .env.production
```

Otvori `.env.production` i obavezno promijeni:

```env
APP_KEY=
DB_PASSWORD=
DB_ROOT_PASSWORD=
MAIL_PASSWORD=
```

APP key možeš generirati ovako:

```bash
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Dobiveni string kopiraj u:

```env
APP_KEY=base64:...
```

## 4. Start

Pokretanje production stacka:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
```

Logovi:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml logs -f app
```

Status:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml ps
```

## 5. Prvi Admin Korisnik

Ne pokretati `db:seed` na produkciji nakon što app ima stvarne podatke.

Za kreiranje ili reset prvog admina:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec app php artisan tinker --execute="App\Models\User::updateOrCreate(['email' => 'admin@stupnik.bike'], ['name' => 'Admin Stupnik', 'password' => Illuminate\Support\Facades\Hash::make('PROMIJENI_OVU_LOZINKU'), 'role' => 'admin', 'phone' => null, 'is_active' => true, 'email_verified_at' => now()]);"
```

Login:

```txt
https://admin.stupnik.bike/admin/login
```

## 6. Update Nove Verzije

Kad prebaciš novu verziju koda:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
```

Laravel entrypoint automatski radi:

```txt
storage:link
migrate --force
config:cache
route:cache
view:cache
```

## 7. Backup Baze

Backup:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec db mysqldump -u root -p sibinjbike > backup-sibinjbike.sql
```

Restore:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec -T db mysql -u root -p sibinjbike < backup-sibinjbike.sql
```
