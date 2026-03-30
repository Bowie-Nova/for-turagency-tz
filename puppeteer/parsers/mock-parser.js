import { BaseParser } from './base-parser.js';

export class MockParser extends BaseParser {
  constructor() {
    super('MockTest');
  }

  async fillFilters(filters) {
    console.log('[Mock] Filters received:', filters);
    await this.page.waitForTimeout(1000);
  }

  async submitSearch() {
    console.log('[Mock] Search submitted (mock)');
    await this.page.waitForTimeout(500);
  }

  async extractTours() {
    console.log('[Mock] Returning test data');
    
    const testTours = [
      {
        title: 'Турция - Стамбул и Анталия 7 ночей',
        hotel_name: 'Grand Hotel Ankara 5*',
        hotel_category: 5,
        departure_city: 'Almaty',
        price: 385000,
        nights: 7,
        departure_date: '2026-04-05',
        available_seats: 3,
        hotel_rating: 4.8,
        inclusions: ['Перелет', 'Отель', 'Завтрак', 'Трансфер'],
        url: 'https://example.com/tour/1',
      },
      {
        title: 'Таиланд - Бангкок и Паттайя 10 ночей',
        hotel_name: 'Centara Hotel 4*',
        hotel_category: 4,
        departure_city: 'Almaty',
        price: 420000,
        nights: 10,
        departure_date: '2026-04-10',
        available_seats: 5,
        hotel_rating: 4.6,
        inclusions: ['Перелет', 'Отель', 'Завтрак', 'Трансфер', 'Экскурсии'],
        url: 'https://example.com/tour/2',
      },
      {
        title: 'Египет - Каир и Красное море 8 ночей',
        hotel_name: 'Hilton Red Sea Resort 5*',
        hotel_category: 5,
        departure_city: 'Almaty',
        price: 365000,
        nights: 8,
        departure_date: '2026-04-12',
        available_seats: 2,
        hotel_rating: 4.7,
        inclusions: ['Перелет', 'Отель', 'Пляж', 'Экскурсии'],
        url: 'https://example.com/tour/3',
      },
      {
        title: 'ОАЭ - Дубай и Абу-Даби 6 ночей',
        hotel_name: 'Atlantis The Palm 5*',
        hotel_category: 5,
        departure_city: 'Almaty',
        price: 450000,
        nights: 6,
        departure_date: '2026-04-08',
        available_seats: 1,
        hotel_rating: 4.9,
        inclusions: ['Перелет', 'Отель', 'Завтрак', 'Шопинг-тур'],
        url: 'https://example.com/tour/4',
      },
      {
        title: 'Малайзия - Куала-Лумпур и Лангкави 12 ночей',
        hotel_name: 'Shangri-La Kuala Lumpur 4*',
        hotel_category: 4,
        departure_city: 'Almaty',
        price: 480000,
        nights: 12,
        departure_date: '2026-04-15',
        available_seats: 4,
        hotel_rating: 4.5,
        inclusions: ['Перелет', 'Отель', 'Завтрак', 'Трансфер', 'Экскурсии'],
        url: 'https://example.com/tour/5',
      },
      {
        title: 'Вьетнам - Ханой и Хошимин 9 ночей',
        hotel_name: 'Sofitel Legend Metropol 4*',
        hotel_category: 4,
        departure_city: 'Almaty',
        price: 350000,
        nights: 9,
        departure_date: '2026-04-06',
        available_seats: 6,
        hotel_rating: 4.4,
        inclusions: ['Перелет', 'Отель', 'Завтрак', 'Трансфер'],
        url: 'https://example.com/tour/6',
      },
    ];

    // Return same structure as other parsers for consistency
    return { tours: testTours, debug_table_html: '' };
  }
}
