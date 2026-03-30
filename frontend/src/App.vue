<style>
    :root {
        --bg: #f4f6f9;
        --card: #ffffff;
        --border: #e1e5eb;
        --text: #1e1e1e;
        --muted: #6b7280;
        --accent: #1f2937;
        --radius: 10px;
    }

    * {
        box-sizing: border-box;
        font-family: "Segoe UI", system-ui, sans-serif;
    }

    body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
    }

    .wrapper {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .card {
        background: var(--card);
        padding: 40px;
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }

    h1 {
        margin-top: 0;
        font-size: 28px;
        font-weight: 600;
    }

    h2 {
        margin: 0;
        font-size: 24px;
    }

    h3 {
        margin: 0;
        font-size: 16px;
    }

    .section {
        margin-top: 30px;
    }

    .section-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--muted);
        margin-bottom: 15px;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .field {
        display: flex;
        flex-direction: column;
    }

    .field label {
        font-size: 13px;
        margin-bottom: 6px;
        color: var(--muted);
        font-weight: 500;
    }

    .field input,
    .field select,
    .field textarea {
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid var(--border);
        font-size: 14px;
        transition: 0.2s;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(31, 41, 55, 0.1);
    }

    .btn {
        padding: 10px 18px;
        border-radius: 6px;
        border: none;
        background: var(--accent);
        color: white;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #111;
    }

    .error-message {
        padding: 12px 16px;
        background: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        color: #991b1b;
        margin-bottom: 20px;
        font-size: 14px;
    }

    /* Tours List */
    .tours-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }

    .tour-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        transition: 0.2s;
    }

    .tour-card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .tour-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 20px;
        background: #f9fafb;
        border-bottom: 1px solid var(--border);
    }

    .tour-operator {
        font-size: 12px;
        color: var(--muted);
        margin: 4px 0 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tour-price {
        text-align: right;
    }

    .price {
        font-size: 20px;
        font-weight: 700;
        color: var(--accent);
    }

    .score {
        font-size: 12px;
        color: var(--muted);
        margin-top: 4px;
    }

    .tour-details {
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
    }

    .detail-item .label {
        color: var(--muted);
        font-weight: 500;
    }

    .tour-actions {
        display: flex;
        gap: 10px;
        padding: 15px 20px;
        background: #f9fafb;
        border-top: 1px solid var(--border);
    }

    .tour-actions .btn {
        flex: 1;
    }

    .tour-actions a {
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<template>
  <div class="wrapper">
      <div class="card" v-if="stage === 'form'">
          <h1>🏨 Поиск туров</h1>

          <div v-if="errorMessage" class="error-message">
              {{ errorMessage }}
          </div>

          <div class="section">
              <div class="section-title">Ваши контакты</div>
              <div class="grid">
                  <div class="field">
                      <label>ФИО *</label>
                      <input type="text" v-model="formData.name" placeholder="Иван Иванов">
                  </div>
                  <div class="field">
                      <label>Телефон *</label>
                      <input type="tel" v-model="formData.phone" placeholder="+7 (777) 123-45-67">
                  </div>
                  <div class="field">
                      <label>Email</label>
                      <input type="email" v-model="formData.email" placeholder="email@example.com">
                  </div>
              </div>
          </div>

          <div class="section">
              <div class="section-title">Параметры тура</div>
              <div class="grid">
                  <div class="field">
                      <label>Город вылета *</label>
                      <select v-model="formData.departure_city">
                          <option value="Almaty">Алматы</option>
                          <option value="Astana">Астана</option>
                          <option value="Karaganda">Караганда</option>
                          <option value="Shymkent">Шымкент</option>
                          <option value="Aktau">Актау</option>
                      </select>
                  </div>
                  <div class="field">
                      <label>Страна назначения *</label>
                      <select v-model="formData.destination_country">
                          <option value="Turkey">Турция</option>
                          <option value="Thailand">Таиланд</option>
                          <option value="Egypt">Египет</option>
                          <option value="UAE">ОАЭ</option>
                          <option value="Malaysia">Малайзия</option>
                          <option value="Vietnam">Вьетнам</option>
                      </select>
                  </div>
                  <div class="field">
                      <label>Категория отеля</label>
                      <select v-model.number="formData.hotel_category">
                          <option value="3">3★</option>
                          <option value="4">4★</option>
                          <option value="5">5★</option>
                      </select>
                  </div>
                  <div class="field">
                      <label>Вылет от *</label>
                      <input type="date" v-model="formData.departure_from">
                  </div>
                  <div class="field">
                      <label>Вылет до *</label>
                      <input type="date" v-model="formData.departure_to">
                  </div>
                  <div class="field">
                      <label>Ночей от</label>
                      <input type="number" min="1" max="30" v-model.number="formData.nights_from">
                  </div>
                  <div class="field">
                      <label>Ночей до</label>
                      <input type="number" min="1" max="30" v-model.number="formData.nights_to">
                  </div>
                  <div class="field">
                      <label>Взрослых</label>
                      <input type="number" min="1" v-model.number="formData.adults">
                  </div>
                  <div class="field">
                      <label>Детей</label>
                      <input type="number" min="0" v-model.number="formData.children">
                  </div>
              </div>
          </div>

          <div class="section">
              <button class="btn" @click="submitSearch">Найти туры</button>
          </div>
      </div>

      <!-- LOADING SCREEN -->
      <div class="card" v-else-if="stage === 'loading'" style="text-align: center; padding: 60px;">
          <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
          <h2>{{ loadingMessage }}</h2>
          <p style="color: var(--muted); margin-top: 10px;">Пожалуйста, не закрывайте эту страницу</p>
      </div>

      <!-- RESULTS SCREEN -->
      <div class="card" v-else-if="stage === 'results'">
          <div style="display: flex; justify-content: space-between; align-items: center;">
              <h1>✅ Найдено туров: {{ tours.length }}</h1>
              <button class="btn-secondary btn" @click="startOver">← Новый поиск</button>
          </div>

          <div v-if="tours.length === 0" style="text-align: center; padding: 40px; color: var(--muted);">
              К сожалению, туров не найдено. Попробуйте изменить критерии поиска.
          </div>

          <div v-else class="tours-list">
              <div v-for="tour in tours" :key="tour.id" class="tour-card">
                  <div class="tour-header">
                      <div>
                          <h3>{{ tour.title }}</h3>
                          <p class="tour-operator">{{ tour.operator.toUpperCase() }}</p>
                      </div>
                      <div class="tour-price">
                          <div class="price">€ {{ tour.price.toLocaleString('ru-RU') }}</div>
                          <div class="score">⭐ {{ tour.popularity_score.toFixed(1) }}</div>
                      </div>
                  </div>

                  <div class="tour-details">
                      <div class="detail-item">
                          <span class="label">Отель:</span>
                          <span>{{ tour.hotel_name || '—' }}</span>
                      </div>
                      <div class="detail-item">
                          <span class="label">Звезд:</span>
                          <span>{{ tour.hotel_category }}★</span>
                      </div>
                      <div class="detail-item">
                          <span class="label">Рейтинг:</span>
                          <span>{{ tour.hotel_rating.toFixed(1) }}/5</span>
                      </div>
                      <div class="detail-item">
                          <span class="label">Дней:</span>
                          <span>{{ tour.days }}</span>
                      </div>
                      <div class="detail-item">
                          <span class="label">Доступные места:</span>
                          <span>{{ tour.available_seats }}</span>
                      </div>
                      <div class="detail-item" v-if="tour.inclusions.length > 0">
                          <span class="label">В цену включено:</span>
                          <span>{{ tour.inclusions.join(', ') }}</span>
                      </div>
                  </div>

                  <div class="tour-actions">
                      <a v-if="tour.url" :href="tour.url" target="_blank" class="btn">Посмотреть подробно</a>
                      <button class="btn-secondary btn">Добавить в корзину</button>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
    import { ref } from 'vue';
    import axios from 'axios'

    const API_URL = 'http://localhost:8000/api';
    
    const stage = ref('form'); // 'form' | 'loading' | 'results'
    const errorMessage = ref('');
    const loadingMessage = ref('');
    const currentLeadId = ref(null);
    const tours = ref([]);

    const formData = ref({
        name: '',
        phone: '',
        email: '',
        departure_city: 'Almaty',
        destination_country: 'Turkey',
        hotel_category: 4,
        departure_from: '',
        departure_to: '',
        nights_from: 5,
        nights_to: 14,
        adults: 2,
        children: 0,
        preferences: []
    });

    async function submitSearch() {
        try {
            errorMessage.value = '';
            
            // Валидация
            if (!formData.value.name.trim()) {
                errorMessage.value = 'Заполните имя';
                return;
            }
            if (!formData.value.phone.trim()) {
                errorMessage.value = 'Заполните телефон';
                return;
            }
            if (!formData.value.departure_from) {
                errorMessage.value = 'Выберите дату вылета "от"';
                return;
            }
            if (!formData.value.departure_to) {
                errorMessage.value = 'Выберите дату вылета "до"';
                return;
            }

            stage.value = 'loading';
            loadingMessage.value = 'Отправка поискового запроса...';

            // Отправка поискового запроса
            const response = await axios.post(`${API_URL}/tours/search`, formData.value);
            
            currentLeadId.value = response.data.lead_id;
            loadingMessage.value = 'Парсим туры (может занять 20-30 сек)...';

            // Polling результатов
            pollResults();

        } catch (error) {
            stage.value = 'form';
            errorMessage.value = error.response?.data?.message || 'Ошибка при отправке запроса';
            console.error(error);
        }
    }

    async function pollResults() {
        const totalWaitMs = 120000; // 120 секунд максимума (парсер ABK работает ~60 сек)
        const intervalMs = 5000; // опрашивать каждые 5 секунд
        const start = Date.now();

        loadingMessage.value = 'Парсим туры... (может занять до 2 минут)';

        while (Date.now() - start < totalWaitMs) {
            try {
                const response = await axios.get(`${API_URL}/tours/${currentLeadId.value}/results`);
                const data = response.data;

                // Проверяем статус в ответе
                if (data.status === 'completed') {
                    // Парсинг завершен — показываем результаты
                    tours.value = (data.tours || []).sort((a, b) => b.popularity_score - a.popularity_score);
                    stage.value = 'results';
                    return;
                } else if (data.status === 'processing') {
                    // Еще обрабатывается — продолжаем ждать
                    const waited = Math.floor((Date.now() - start) / 1000);
                    loadingMessage.value = `Парсим туры... (${waited} сек)`;
                    await new Promise(resolve => setTimeout(resolve, intervalMs));
                    continue;
                } else if (data.status === 'failed') {
                    // Ошибка при парсинге
                    stage.value = 'form';
                    errorMessage.value = 'Ошибка при поиске туров. Попробуйте позже.';
                    return;
                }
            } catch (error) {
                // Сетевые или другие ошибки — логируем и продолжаем ждать
                const waited = Math.floor((Date.now() - start) / 1000);
                
                if (error.response?.status === 400) {
                    stage.value = 'form';
                    errorMessage.value = 'Ошибка валидации при поиске туров';
                    return;
                }
                
                loadingMessage.value = `Ожидание ответа... (${waited} сек)`;
                await new Promise(resolve => setTimeout(resolve, intervalMs));
            }
        }

        // По истечении 120 секунд — показываем страницу результатов с 0 турами
        tours.value = [];
        stage.value = 'results';
        loadingMessage.value = '';
    }

    function startOver() {
        stage.value = 'form';
        errorMessage.value = '';
        tours.value = [];
        currentLeadId.value = null;
    }
</script>