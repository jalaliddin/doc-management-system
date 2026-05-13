# Urganchtransgaz — Hujjat Tayyorlash Tizimi

**"Urganchtransgaz" UE** markaziy apparat xodimlari uchun rasmiy hujjatlarni Word formatida tayyorlash veb-tizimi.

---

## Texnologiyalar

| Qatlam | Stack |
|--------|-------|
| Backend | Laravel 11, PHP 8.3, MySQL 8.0 |
| Frontend | Vue 3 (Composition API), Vuetify 3, Vite |
| Autentifikatsiya | Laravel Sanctum (token-based) |
| Word generatsiya | phpoffice/phpword |
| AI | Google Gemini 1.5 Flash (grammatika tuzatish) |
| Server | Nginx + PHP-FPM |
| Container | Docker + Docker Compose |

---

## Loyiha tuzilmasi

```
urganchtransgaz/
├── backend/                # Laravel 11 API
├── frontend/               # Vue 3 + Vuetify 3 SPA
├── docker/
│   └── nginx/
│       └── app.conf        # Nginx konfiguratsiyasi
├── docker-compose.yml      # Docker Compose (production)
├── .env.docker             # Docker environment o'zgaruvchilari
└── api.http                # API test fayli (VS Code REST Client)
```

---

## Docker orqali ishga tushurish (VPS)

### Talablar
- Docker Engine 24+
- Docker Compose v2+
- Port **8093** ochiq bo'lishi kerak

### 1. Repozitoriyani klonlash

```bash
git clone <repo-url> /var/www/hujjat
cd /var/www/hujjat
```

### 2. Environment sozlash

```bash
# .env.docker faylini ko'rib chiqing va kerakli qiymatlarni o'zgartiring
nano .env.docker
```

Muhim o'zgaruvchilar:
```env
APP_KEY=base64:...       # php artisan key:generate bilan yangilang
DB_PASSWORD=root         # Ishonchli parol kiriting
GEMINI_API_KEY=AIza...   # Gemini API kalitini kiriting (ixtiyoriy)
APP_URL=http://hujjat.urtg.uz:8093
```

### 3. Konteynerlarni qurish va ishga tushurish

```bash
# Barcha servislarni qurish va ishga tushurish
docker compose up -d --build

# Birinchi marta: ma'lumotlar bazasini sozlash
docker compose run --rm migrate

# Loglarni kuzatish
docker compose logs -f
```

### 4. Tekshirish

```bash
# Servislar holati
docker compose ps

# Ilovaga kirish
curl http://localhost:8093
# yoki brauzerda: http://hujjat.urtg.uz:8093
```

**Admin panel:** `http://hujjat.urtg.uz:8093/admin/login`
- Login: `admin`
- Parol: `Admin@2024`

---

## Rivojlantirish muhiti (local)

```bash
# Backend
cd backend
composer install
cp .env.example .env
# .env ni sozlang (DB, Gemini)
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000

# Frontend (yangi terminal)
cd frontend
npm install
npm run dev
# http://localhost:5173
```

---

## Docker buyruqlari

```bash
# Servislarni to'xtatish
docker compose down

# Ma'lumotlar bazasini saqlab to'xtatish
docker compose stop

# Loglar
docker compose logs nginx
docker compose logs php
docker compose logs db

# PHP konteyneri ichiga kirish
docker compose exec php sh

# MySQL ga ulanish
docker compose exec db mysql -uroot -proot urganchtransgaz_docs

# Keshlarni tozalash
docker compose exec php php artisan cache:clear
docker compose exec php php artisan config:clear

# Qayta qurish (kod yangilangandan so'ng)
docker compose up -d --build nginx php
```

---

## API endpointlari

### Ochiq (autentifikatsiya shart emas)
| Metod | URL | Tavsif |
|-------|-----|--------|
| GET | `/api/departments` | Barcha bo'limlar |
| GET | `/api/departments/{id}` | Bitta bo'lim |
| GET | `/api/organizations` | Barcha tashkilotlar |
| GET | `/api/organizations?type=yuqori` | Tur bo'yicha filter |
| GET | `/api/organizations/{id}/leaders` | Rahbarlar ro'yxati |
| GET | `/api/signatories` | Imzolovchilar |
| POST | `/api/documents/generate` | Word hujjat generatsiya |

### Admin (Sanctum token kerak)
| Metod | URL | Tavsif |
|-------|-----|--------|
| POST | `/api/admin/login` | Login |
| POST | `/api/admin/logout` | Chiqish |
| GET/POST/PUT/DELETE | `/api/admin/departments` | Bo'limlar CRUD |
| GET/POST/PUT/DELETE | `/api/admin/organizations` | Tashkilotlar CRUD |
| GET/POST/PUT/DELETE | `/api/admin/signatories` | Imzolovchilar CRUD |

---

## Muallif

**AT xizmati** — J.S. Saidov  
"Urganchtransgaz" UE
