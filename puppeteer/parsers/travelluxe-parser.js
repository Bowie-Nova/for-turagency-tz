import { BaseParser } from './base-parser.js';

export class TravelluxeParser extends BaseParser {
  constructor() {
    super('Travelluxe');
  }

  async fillFilters(filters) {
    try {
      if (filters.departure_city) {
        await this.type('input[placeholder*="город"]', filters.departure_city);
        await this.page.waitForTimeout(500);
      }

      if (filters.destination_country) {
        await this.type('input[placeholder*="страна"]', filters.destination_country);
        await this.page.waitForTimeout(500);
      }

      if (filters.departure_from) {
        await this.type('input[type="date"][name*="from"]', filters.departure_from);
        await this.page.waitForTimeout(300);
      }

      if (filters.departure_to) {
        await this.type('input[type="date"][name*="to"]', filters.departure_to);
        await this.page.waitForTimeout(300);
      }

      if (filters.nights_from) {
        await this.type('input[name*="duration"]', filters.nights_from.toString());
        await this.page.waitForTimeout(300);
      }
    } catch (error) {
      console.error('[Travelluxe] fillFilters error:', error.message);
    }
  }

  async submitSearch() {
    try {
      const buttons = await this.page.$$('button');
      for (const button of buttons) {
        const text = await this.page.evaluate(el => el.innerText, button);
        if (text.match(/Поиск|Найти|Search/i)) {
          await button.click();
          await this.page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {});
          await this.page.waitForTimeout(3000);
          break;
        }
      }
    } catch (error) {
      console.warn('[Travelluxe] submitSearch error:', error.message);
    }
  }

  async extractTours() {
    try {
      const tours = await this.page.evaluate(() => {
        const rows = document.querySelectorAll('.resultset table.res tbody tr');
        const results = [];

        rows.forEach(row => {
          try {
            const title = row.querySelector('.tour')?.innerText?.trim() || row.querySelector('td.tour')?.innerText?.trim() || '';
            const hotel = row.querySelector('.link-hotel')?.innerText?.trim() || '';
            const priceSpan = row.querySelector('span.price');
            let price = 0;
            if (priceSpan) {
              price = parseInt(priceSpan.dataset?.convertedPriceNumber || priceSpan.getAttribute('data-converted-price-number') || priceSpan.innerText.replace(/\D/g, '')) || 0;
            }
            const nights = parseInt(row.dataset.nights || row.getAttribute('data-nights')) || parseInt(row.querySelector('.c')?.innerText?.match(/\d+/)?.[0]) || 0;
            const checkin = row.dataset.checkin || row.getAttribute('data-checkin') || '';
            const departure_date = checkin && checkin.length===8 ? `${checkin.slice(0,4)}-${checkin.slice(4,6)}-${checkin.slice(6,8)}` : new Date().toISOString().split('T')[0];

            if (price > 0) {
              results.push({
                title,
                hotel_name: hotel,
                hotel_category: 4,
                price,
                nights: nights || 7,
                departure_date,
                available_seats: 5,
                hotel_rating: parseFloat(row.querySelector('[class*="rating"]')?.innerText?.match(/\d+\.?\d*/)?.[0]) || 0,
                inclusions: [],
                url: row.querySelector('a')?.href || '',
              });
            }
          } catch (e) {
            // ignore
          }
        });

        return results;
      });

      // capture debug HTML (tbody or first result table)
      const debugHtml = await this.page.evaluate(() => {
        const tbody = document.querySelector('.resultset table.res tbody') || document.querySelector('table.res tbody');
        if (tbody) return tbody.outerHTML;
        const table = document.querySelector('.resultset table') || document.querySelector('table');
        return table ? table.outerHTML : '';
      });

      console.log(`[Travelluxe] Extracted ${tours.length} tours`);
      return { tours, debug_table_html: debugHtml };
    } catch (error) {
      console.error('[Travelluxe] extractTours error:', error.message);
      return [];
    }
  }
}
