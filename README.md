SPA-приложение для управления личными задачами с напоминаниями.

# Как запустить

```bash
# 1. Клонировать репозиторий и перейти в папку
cd tasks-app

# 2. Скопировать .env
cp .env.example .env

# 3. Запустить контейнеры
docker-compose up -d --build

# 4. Установить зависимости Laravel
docker-compose exec app composer install

# 5. Сгенерировать APP_KEY
docker-compose exec app php artisan key:generate

# 6. Выполнить миграции
docker-compose exec app php artisan migrate

# 7. Запустить фронтенд (в отдельном терминале)
cd frontend
npm install
npm run dev