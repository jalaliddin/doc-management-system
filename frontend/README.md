# Frontend — Vue 3 + Vuetify 3 SPA

**Urganchtransgaz Hujjat Tizimi** uchun zamonaviy veb-interfeys.

---

## Talablar

- Node.js 22+
- npm 10+

---

## O'rnatish (local)

```bash
npm install
npm run dev
# http://localhost:5173
```

Ishlab chiqish uchun `.env` fayli:
```env
VITE_API_BASE_URL=http://localhost:8000/api
```

---

## Loyiha tuzilmasi

```
frontend/
├── src/
│   ├── views/
│   │   ├── HomeView.vue            # Bo'limlar ro'yxati (/)
│   │   ├── DocumentForm.vue        # Hujjat shakli (/department/:id/new)
│   │   ├── AdminLogin.vue          # Admin login (/admin/login)
│   │   └── admin/
│   │       ├── DepartmentManager.vue   # Bo'limlar CRUD
│   │       ├── OrganizationManager.vue # Tashkilotlar CRUD
│   │       └── SignatoryManager.vue    # Imzolovchilar CRUD
│   ├── components/
│   │   ├── AppSidebar.vue          # Yon panel (public/admin mode)
│   │   └── AdminLayout.vue         # Admin sahifalari uchun wrapper
│   ├── stores/
│   │   └── auth.js                 # Pinia: admin autentifikatsiya
│   ├── router/
│   │   └── index.js                # Vue Router (route guard bilan)
│   └── plugins/
│       ├── vuetify.js              # Vuetify 3 + MDI ikonlar
│       └── axios.js                # API client (token interceptor)
├── .env                            # Development API URL
├── .env.production                 # Production API URL (/api)
├── Dockerfile                      # Multi-stage: Node build + Nginx
└── vite.config.js
```

---

## Sahifalar

| Sahifa | URL | Tavsif |
|--------|-----|--------|
| Bosh sahifa | `/` | Bo'limlar kartalar ko'rinishida |
| Hujjat shakli | `/department/:id/new` | Forma to'ldirish va Word yuklab olish |
| Admin login | `/admin/login` | Faqat admin kirishi |
| Bo'limlar | `/admin/departments` | CRUD jadval |
| Tashkilotlar | `/admin/organizations` | Tablar + rahbarlar accordion |
| Imzolovchilar | `/admin/signatories` | CRUD jadval |

---

## Dizayn

```
Asosiy ranglar:
  Sidebar:  #0D1B2A (to'q ko'k)
  Accent:   #0096C7 → #00B4D8 (gradient)
  Fon:      #F8FAFC

Shriftlar:
  Asosiy:   Rubik (Google Fonts)
  Kontent:  IBM Plex Sans (Google Fonts)
```

---

## Build (production)

```bash
npm run build
# dist/ papkasiga yig'iladi
```

Docker build avtomatik `npm run build` chaqiradi va statik fayllar Nginx orqali xizmat ko'rsatadi.

---

## API ulanish

Production da barcha `/api/*` so'rovlari Nginx tomonidan PHP-FPM ga proksi qilinadi.  
Development da Vite proxy (`vite.config.js`) `localhost:8000` ga yo'naltiradi.
