# Backend — Laravel 11 API

**Urganchtransgaz Hujjat Tizimi** uchun RESTful API va Word hujjat generatsiya xizmati.

---

## Talablar

- PHP 8.3+
- Composer 2.7+
- MySQL 8.0+

---

## O'rnatish (local)

```bash
composer install

cp .env.example .env

# .env ni sozlang:
# DB_DATABASE=urganchtransgaz_docs
# DB_USERNAME=root
# DB_PASSWORD=root
# GEMINI_API_KEY=AIza...  (ixtiyoriy)

php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

---

## Loyiha tuzilmasi

```
backend/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php          # Admin login/logout
│   │   ├── DepartmentController.php    # Bo'limlar CRUD
│   │   ├── DocumentController.php      # Word generatsiya + Gemini
│   │   ├── OrganizationController.php  # Tashkilotlar + rahbarlar CRUD
│   │   └── SignatoryController.php     # Imzolovchilar CRUD
│   └── Models/
│       ├── AdminUser.php
│       ├── Department.php
│       ├── Organization.php
│       ├── OrganizationLeader.php
│       └── Signatory.php
├── database/
│   ├── migrations/                     # 5 ta migration
│   └── seeders/                        # Namuna ma'lumotlar
├── routes/
│   └── api.php                         # Barcha API route lar
├── config/
│   ├── cors.php                        # CORS sozlamalari
│   └── sanctum.php                     # Sanctum sozlamalari
├── Dockerfile                          # PHP-FPM container
└── docker-entrypoint.sh
```

---

## Ma'lumotlar bazasi

| Jadval | Tavsif |
|--------|--------|
| `departments` | Bo'limlar (indeks kodi, rahbar) |
| `organizations` | Tashkilotlar (yuqori/quyi/boshqa) |
| `organization_leaders` | Tashkilot rahbarlari |
| `signatories` | Imzolovchilar (markaziy apparat) |
| `admin_users` | Admin foydalanuvchilar |
| `personal_access_tokens` | Sanctum tokenlari |

**Default admin:** `admin` / `Admin@2024`

---

## Word hujjat generatsiya

`POST /api/documents/generate` so'rovi:

```json
{
  "department_id": 1,
  "organization_id": 2,
  "organization_leader_id": 3,
  "signatory_id": 1,
  "document_number": "125",
  "document_date": "2026-05-13",
  "text_content": "Hujjat matni...",
  "gemini_api_key": "AIza..."
}
```

Javob: `.docx` fayl (binary download).

**Hujjat raqami formati:** `{index_code}{document_number}` — masalan `АТ/125`

---

## Gemini API

Matnning grammatik xatolarini tuzatish uchun `gemini-1.5-flash` modeli ishlatiladi.

Kalit manbai (ustuvorlik tartibida):
1. Request body dagi `gemini_api_key`
2. `.env` dagi `GEMINI_API_KEY`
3. Agar ikkalasi ham yo'q — matn o'zgartirilmaydi

---

## Muhim env o'zgaruvchilar

| O'zgaruvchi | Tavsif |
|-------------|--------|
| `APP_KEY` | Laravel shifrlash kaliti |
| `DB_HOST` | MySQL host (Docker: `db`) |
| `DB_DATABASE` | `urganchtransgaz_docs` |
| `GEMINI_API_KEY` | Gemini API kaliti (ixtiyoriy) |
