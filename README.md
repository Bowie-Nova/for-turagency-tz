# Тур-ассоциация сметс платформа

## Установка и запуск

### Требования
- PHP 8.3+
- Node.js 22.17.0+
- Laravel 11
- Vue 3
- SQLite

### Backend (Laravel)

#### 1. Установка зависимостей
```bash
cd laravel
composer install
```

#### 2. Конфигурация базы данных
База данных автоматически создается при миграции (SQLite в `database/database.sqlite`)

#### 3. Запуск миграций
```bash
php artisan migrate
```

### Frontend (Vue)

```bash
cd frontend
npm install
npm run dev
```

### Node.js Puppeteer Parser

#### 1. Установка зависимостей
```bash
cd puppeteer
npm install
```

**Если npm install не работает на Windows:**
- Очистите: `rm -r node_modules package-lock.json`
- Переустановите: `npm install --legacy-peer-deps`

#### 2. Тестирование парсера
```bash
node index.js <<'EOF'
{
  "departure_city": "Алматы",
  "destination_country": "Турция",
  "check_in_from": "2026-04-01",
  "check_in_to": "2026-04-08",
  "days": 7,
  "adults": 2,
  "children": 0,
  "hotel_category": 4
}
EOF
```

### Запуск приложения

#### Во время разработки (3 разных терминала):

**Терминал 1 - Laravel сервер API:**
```bash
cd laravel
php artisan serve  # http://localhost:8000
```

**Терминал 2 - Vue dev сервер:**
```bash
cd frontend
npm run dev  # http://localhost:5173
```

**Терминал 3 - Laravel Queue Worker (для асинхронного парсинга):**
```bash
cd laravel
php artisan queue:work
```

## API Endpoints

### POST /api/tours/search
Поиск туров по критериям

> **Как это работает**: Каждый поиск запускает парсер туров (Puppeteer + Node.js).
> Парсер подключается к сайту ABK Tourism, заполняет форму поиска, нажимает кнопку,
> и извлекает результаты из таблицы. HTML результатов автоматически сохраняется 
> для отладки. Затем туры фильтруются по критериям и возвращаются в API.

**Request:**
```json
{
  "name": "Иван Иванов",
  "phone": "+7 (777) 123-45-67",
  "email": "ivan@example.com",
  "departure_city": "Алматы",
  "destination_country": "Турция",
  "hotel_category": 4,
  "departure_from": "2026-04-01",
  "departure_to": "2026-04-08",
  "nights_from": 7,
  "nights_to": 7,
  "adults": 2,
  "children": 1,
  "preferences": []
}
```

**Response (202 Accepted):**
```json
{
  "status": "processing",
  "message": "Поиск туров запущен. Пожалуйста, подождите...",
  "lead_id": 1,
  "check_later": "/api/tours/1/results"
}
```

### GET /api/tours/{lead_id}/results
Получение результатов поиска

**Response (если еще обрабатывается):**
```json
{
  "status": "processing",
  "message": "Поиск в процессе. Пожалуйста, подождите..."
}
```

**Response (готово):**
```json
{
  "status": "completed",
  "lead": { ... },
  "tours": [
    {
      "id": 1,
      "lead_id": 1,
      "operator": "selfie",
      "title": "Тур на Средиземное море",
      "hotel_name": "5-звездочный отель",
      "hotel_category": 5,
      "price": 450000,
      "days": 7,
      "departure_date": "2026-04-01",
      "available_seats": 5,
      "hotel_rating": 4.8,
      "inclusions": ["Перелет", "Отель", "Всё включено"],
      "url": "https://...",
      "popularity_score": 95.5,
      "created_at": "2026-02-26T11:10:00Z"
    }
  ],
  "count": 25
}
```

## Архитектура

### Backend структура

```
laravel/
├── app/
│   ├── Models/
│   │   ├── Lead.php          # Модель поискового запроса
│   │   └── Tour.php          # Модель результата тура
│   ├── Jobs/
│   │   └── ParseToursJob.php # Фоновая задача парсинга
│   ├── Services/
│   │   ├── PuppeteerParserService.php    # Интеграция с Node.js
│   │   ├── TourAggregatorService.php     # Фильтрация и сортировка
│   │   └── TourParserService.php         # Управление операторами
│   └── Http/
│       └── Controllers/
│           └── TourController.php        # REST API контроллер
├── database/
│   ├── migrations/
│   │   ├── 2026_02_26_110203_create_leads_table.php
│   │   └── 2026_02_26_110204_create_tours_table.php
│   └── database.sqlite                   # Database file
└── routes/
    └── api.php               # API маршруты
```

### Node.js Puppeteer структура

```
puppeteer/
├── index.js              # Main dispatcher
├── package.json          # Dependencies
└── parsers/
    ├── base-parser.js    # Abstract base class
    ├── selfie-parser.js
    ├── abk-parser.js
    ├── travelluxe-parser.js
    ├── kazunion-parser.js
    ├── fstravel-parser.js
    └── crystalbay-parser.js
```

## Поток данных

1. **Frontend VUE** → отправляет форму с критериями
2. **TourController.search()** → создает Lead запись, вызывает `ParseToursJob`
3. **ParseToursJob** → запускает Node.js парсер через `PuppeteerParserService`
4. **puppeteer/index.js** → загружает каждый оператор в браузер параллельно
5. **Парсеры** → извлекают туры со страниц
6. **TourAggregatorService** → фильтрует, оценивает (scoring), сортирует результаты
7. **Database** → сохраняет Tour записи с lead_id
8. **Frontend** → вызывает `/api/tours/{lead_id}/results` для получения результатов (с polling)

## Алгоритм оценки туров

Каждый тур получает оценку по формуле:

```
popularity_score = (price_score × 0.4) + (availability × 0.2) + (rating × 0.1) + (preferences × 0.3)
```

- **40% - Цена**: Более низкие цены получают более высокие баллы
- **20% - Доступность**: Количество свободных мест
- **10% - Рейтинг отеля**: Средний рейтинг из парсингов
- **30% - Соответствие предпочтениям**: Соответствие фильтрам пользователя

## Поддерживаемые операторы

1. **Selfie Travel** - b2b.selfietravel.kz
2. **ABK Tourism** - b2b.abktourism.kz
3. **Travelluxe** - online.travelluxe.kz
4. **Kazunion** - online.kazunion.com
5. **Fstravel** - b2b.fstravel.asia
6. **Crystal Bay** - booking-kz.crystalbay.com

## Проблемы и решения

### npm install на Windows
Если при установке puppeteer появляется ошибка EPERM, используйте:
```bash
npm install --legacy-peer-deps
```

### Database уже существует
Если таблица tours уже создана, выполните:
```bash
php artisan migrate:refresh  # Удаляет и пересоздает все таблицы
```

### Парсер долго работает
Максимальное время парсинга - 5 минут (300 сек). Если он дольше, увеличьте timeout в index.js

## Логирование

- Laravel логи: `laravel/storage/logs/`
- Node.js вывод: Видно в терминале при запуске queue worker
- Статус парсинга: смотрите в базе `leads.status` (new/processing/completed/failed)

## Дальнейшее развитие

- [ ] Добавить кэширование результатов на 24ч
- [ ] Реализовать фильтрацию по отзывам туристов
- [ ] Интеграция с платежными системами
- [ ] Мобильное приложение
- [ ] WhatsApp/Telegram уведомления
- [ ] Сравнение цен в реальном времени
