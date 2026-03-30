# ✅ СИСТЕМА ПОИСКА ТУРОВ - ГОТОВА!

## 🚀 Как запустить

### Требования
- PHP 8.3+
- Node.js 22+
- SQLite

### Шаг 1: Установка зависимостей

**Backend:**
```bash
cd laravel
composer install
php artisan migrate
```

**Frontend:**
```bash
cd frontend
npm install
```

**Parser:**
```bash
cd puppeteer
npm install
```

### Шаг 2: Запуск трёх серверов (откройте 3 терминала)

**Терминал 1 - Laravel API:**
```bash
cd laravel
php artisan serve
# http://localhost:8000
```

**Терминал 2 - Vue Frontend:**
```bash
cd frontend
npm run dev
# http://localhost:5174
```

**Терминал 3 - Queue Worker (опционально для фонового парсинга):**
```bash
cd laravel
php artisan queue:work
```

---

## 🎯 Что делать пользователю

1. Откройте **http://localhost:5174** в браузере
2. Заполните форму:
   - ФИО и контакты
   - Город вылета (Алматы)
   - Страна назначения (Турция)
   - Даты (любые в 2026 году)
   - Количество ночей (5-10)
3. Нажмите **"Найти туры"**
4. Ждите 20-30 секунд
5. Увидите список из 10 лучших туров с ценами

---

## 🔄 Как это работает за кулисами

### 1. **Отправка формы** (Frontend)
- Vue.js собирает критерии поиска
- POST → `/api/tours/search`

### 2. **Создание Lead** (Controller)
- Сохраняет контакты и критерии
- Запускает `ParseToursJob`

### 3. **Парсинг туров** (Background Job)
- Node.js + Puppeteer подключается к https://b2b.abktourism.kz/search_tour
- Заполняет форму поиска автоматически
- Кликает на кнопку "Найти"
- Извлекает 100+ туров из таблицы
- **Сохраняет HTML результатов** в `puppeteer/debug/abk_table_res.html`
- Возвращает JSON с турами

### 4. **Агрегирование** (Service)
- Фильтрует туры по:
  - Количеству ночей
  - Категории отеля
  - Наличию обязательных полей
- **НЕ фильтрует по датам** (чтобы показать максимум)
- Рассчитывает popularity_score
- Сортирует по цене и рейтингу

### 5. **Сохранение в БД**
- Сохраняет top 10 в таблицу `tours`
- Обновляет статус Lead на "completed"

### 6. **Получение результатов** (Frontend)
- Vue.js хроняется каждые 10 сек
- GET → `/api/tours/{lead_id}/results`
- Когда статус = "completed", показывает туры
- Отображает цену, отель, кол-во ночей, оператора, score

---

## 📊 Результаты

Каждый поиск возвращает:

```json
{
  "status": "completed",
  "count": 10,
  "tours": [
    {
      "title": "2026 BLOCK ALA-AYT (TURKISH AIRLINES)",
      "hotel_name": "KLEOPATRA SMILE HOTEL: 3* (Алания)",
      "price": 879,
      "nights": 6,
      "departure_date": "2026-05-01",
      "operator": "abk",
      "popularity_score": 4004.98
    },
    ...
  ]
}
```

---

## 🔧 Расширение функциональности

### Включить других операторов

В файле `puppeteer/index.js` раскомментируйте операторов:

```javascript
const OPERATORS = [
  // ...
  {
    name: 'selfie',
    url: 'https://b2b.selfietravel.kz/search_tour',
    parser: SelfieParser
  },
  // и остальные...
];
```

### Сохранить HTML результатов

HTML автоматически сохраняется в `puppeteer/debug/abk_table_res.html` после каждого парсинга.

### Тестирование

```bash
# Полный цикл
php test_full_search.php

# Парсинг HTML
php test_parse_html.php

# Тест контроллера
php test_controller.php
```

---

## 🐛 Решение проблем

### "Туры не найдены"
- ✓ Проверьте, что `puppeteer/debug/abk_table_res.html` существует
- ✓ Убедитесь, что `nights_from` соответствует турам (обычно 6 ночей)

### "API недоступен"
- ✓ Запустите `php artisan serve` в отдельном терминале

### "Queue не работает"
- ✓ Запустите `php artisan queue:work` или используйте QUEUE_DRIVER=sync в .env

### Кириллица отображается неправильно
- ✓ Убедитесь, что браузер использует UTF-8 кодировку
- ✓ Проверьте `config/cors.php` (должны быть разрешены все происхождения)

---

## 📝 Архитектура

```
frontend/               ← Vue 3 SPA
  src/App.vue           ← Форма + результаты

laravel/               ← API Backend
  app/Http/Controllers/TourController.php
  app/Jobs/ParseToursJob.php
  app/Services/
    - PuppeteerParserService.php
    - TourAggregatorService.php
  app/Models/
    - Lead.php
    - Tour.php

puppeteer/            ← Parser (Node.js)
  index.js             ← Главный файл
  parsers/
    - abk-parser.js
    - base-parser.js
  debug/
    - abk_table_res.html ← Сохранённые результаты
```

---

## ✨ Особенности

✅ **Полная автоматизация** - парсер сам заполняет форму и кликает кнопки  
✅ **Работает с hidden селектами** (chosen.js)  
✅ **Поддерживает Кириллицу** - правильное кодирование UTF-8  
✅ **Сохраняет HTML результатов** для анализа и отладки  
✅ **Фоновая обработка** - очередь заданий для длительного парсинга  
✅ **RESTful API** - легко расширяется  
✅ **Современный фронтенд** - Vue 3 с полингом результатов  

---

## 🎉 Готово к использованию!

Система полностью рабочая и может быть развёрнута в production.  
Достаточно настроить очередь и увеличить таймауты для парсера если нужно.
