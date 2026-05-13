# ===== Stage 1: Vue ilovasini build qilish =====
FROM node:22-alpine AS builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

COPY . .
RUN npm run build

# ===== Stage 2: Nginx orqali xizmat ko'rsatish =====
FROM nginx:1.27-alpine

# Build artifaktlarni nusxalash
COPY --from=builder /app/dist /usr/share/nginx/html

# Nginx konfiguratsiyasi (docker-compose dan mount qilinadi)
# fallback uchun default.conf ni o'chirish
RUN rm /etc/nginx/conf.d/default.conf

EXPOSE 80
