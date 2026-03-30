import { BaseParser } from './base-parser.js';

export class AbkParser extends BaseParser {
  constructor() {
    super('ABK');
  }

  async fillFilters(filters) {
    try {
      console.log('[ABK] Starting fillFilters');
      
      // Convert date from YYYY-MM-DD to DD.MM.YYYY format
      const formatDateForABK = (dateStr) => {
        if (!dateStr) return '';
        const [year, month, day] = dateStr.split('-');
        return `${day}.${month}.${year}`;
      };

      // City: TOWNFROMINC select
      if (filters.departure_city) {
        const cityMap = {
          'Almaty': '10',
          'Astana': '12',
          'Aktobe': '11',
          'Karaganda': '13',
          'Shymkent': '14'
        };
        const cityValue = cityMap[filters.departure_city] || filters.departure_city;
        console.log(`[ABK] Selecting city: ${filters.departure_city} -> ${cityValue}`);
        await this.selectChosen('select[name="TOWNFROMINC"]', cityValue);
        await this.page.waitForTimeout(500);
      }

      // Country: STATEINC select
      if (filters.destination_country) {
        const countryMap = {
          'Turkey': '6',
          'Bulgaria': '7',
          'Greece': '8',
          'Egypt': '9',
          'UAE': '10'
        };
        const countryValue = countryMap[filters.destination_country] || filters.destination_country;
        console.log(`[ABK] Selecting country: ${filters.destination_country} -> ${countryValue}`);
        await this.selectChosen('select[name="STATEINC"]', countryValue);
        await this.page.waitForTimeout(500);
      }

      // Departure from: CHECKIN_BEG input (DD.MM.YYYY format)
      if (filters.departure_from) {
        const fromDate = formatDateForABK(filters.departure_from);
        console.log(`[ABK] Typing departure from: ${fromDate}`);
        await this.setDateInput('input[name="CHECKIN_BEG"]', fromDate);
        await this.page.waitForTimeout(300);
      }

      // Departure to: CHECKIN_END input (DD.MM.YYYY format)
      if (filters.departure_to) {
        const toDate = formatDateForABK(filters.departure_to);
        console.log(`[ABK] Typing departure to: ${toDate}`);
        await this.setDateInput('input[name="CHECKIN_END"]', toDate);
        await this.page.waitForTimeout(300);
      }

      // Nights from: NIGHTS_FROM select
      if (filters.nights_from) {
        console.log(`[ABK] Selecting nights from: ${filters.nights_from}`);
        await this.selectChosen('select[name="NIGHTS_FROM"]', filters.nights_from.toString());
        await this.page.waitForTimeout(300);
      }

      // Nights till: NIGHTS_TILL select
      if (filters.nights_to) {
        console.log(`[ABK] Selecting nights till: ${filters.nights_to}`);
        await this.selectChosen('select[name="NIGHTS_TILL"]', filters.nights_to.toString());
        await this.page.waitForTimeout(300);
      }

      // Adults: ADULT select
      if (filters.adults) {
        console.log(`[ABK] Selecting adults: ${filters.adults}`);
        await this.selectChosen('select[name="ADULT"]', filters.adults.toString());
        await this.page.waitForTimeout(300);
      }

      // Children: CHILD select
      if (filters.children) {
        console.log(`[ABK] Selecting children: ${filters.children}`);
        await this.selectChosen('select[name="CHILD"]', filters.children.toString());
        await this.page.waitForTimeout(300);
      }

      console.log('[ABK] fillFilters completed');
    } catch (error) {
      console.error('[ABK] fillFilters error:', error.message);
    }
  }

  async submitSearch() {
    try {
      console.log('[ABK] Starting submitSearch');
      
      // Click the search button: <button class="load right">Искать</button>
      const clicked = await this.page.evaluate(() => {
        const btn = document.querySelector('button.load');
        if (btn) {
          btn.click();
          return true;
        }
        return false;
      });
      
      if (!clicked) {
        console.warn('[ABK] Search button not found');
        return;
      }
      
      console.log('[ABK] Search button clicked');
      
      // Wait for page to respond after button click
      await this.page.waitForTimeout(2000);
      
      // Try waitForFunction with a reasonable timeout to detect results
      try {
        const hasResults = await Promise.race([
          this.page.waitForFunction(
            () => {
              const div = document.querySelector('.resultset');
              const table = document.querySelector('table.res');
              return (div && div.children.length > 0) || (table && table.rows.length > 1);
            },
            { timeout: 15000 }
          ),
          new Promise(resolve => {
            setTimeout(() => resolve(false), 15000);
          })
        ]);
        
        if (hasResults) {
          console.log('[ABK] Results detected via function wait');
        } else {
          console.warn('[ABK] No results detected within timeout');
        }
      } catch (e) {
        console.warn('[ABK] Results wait timeout/error:', e.message);
      }
      
      // Additional wait to ensure rendering
      await this.page.waitForTimeout(1000);
      console.log('[ABK] submitSearch completed');
    } catch (error) {
      console.warn('[ABK] submitSearch error:', error.message);
    }
  }

  async extractTours() {
    try {
      console.log('[ABK] Starting extractTours');
      
      // First, check what selectors are finding
      const debugInfo = await this.page.evaluate(() => {
        return {
          rowCount: document.querySelectorAll('table.res tbody tr').length,
          altRowCount: document.querySelectorAll('table.resultset tr').length,
          allTableCount: document.querySelectorAll('table').length,
          resultsetDiv: !!document.querySelector('.resultset'),
          resultsetContent: document.querySelector('.resultset')?.children.length || 0,
        };
      });
      console.log('[ABK] Debug info:', JSON.stringify(debugInfo));

      const htmlDebug = await this.page.evaluate(() => {
        // Check what's in the body and resultset
        const resultDiv = document.querySelector('.resultset');
        let content = '';
        let tdCount = 0;
        let imgCount = 0;
        
        if (resultDiv) {
          content = resultDiv.outerHTML.substring(0, 2000);
          tdCount = resultDiv.querySelectorAll('td').length;
          imgCount = resultDiv.querySelectorAll('img').length;
        }
        
        return {
          hasResultDiv: !!resultDiv,
          resultDivHtml: content,
          tdInResult: tdCount,
          imagesInResult: imgCount,
        };
      });
      console.log('[ABK] HTML debug:', JSON.stringify(htmlDebug));

      const tours = await this.page.evaluate(() => {
        let rows = document.querySelectorAll('table.res tbody tr');
        if (rows.length === 0) rows = document.querySelectorAll('.resultset table tr');
        if (rows.length === 0) rows = document.querySelectorAll('table tr');

        const results = [];
        const debugRows = [];

        rows.forEach((row, index) => {
          try {
            if (row.querySelector('th')) return;

            const rowHtml = row.outerHTML || '';

            // Title heuristics
            let title = '';
            const tourCell = row.querySelector('td.tour') || row.querySelector('td.link-hotel') || row.querySelector('td.name') || row.querySelector('td a') || row.querySelector('td');
            if (tourCell) {
              title = (tourCell.innerText || '').trim().split('\n').map(s => s.trim()).filter(Boolean)[0] || '';
            }

            // Hotel name heuristics
            let hotel = '';
            const hotelCell = row.querySelector('td.link-hotel') || row.querySelector('td.hotel') || row.querySelector('td.name');
            if (hotelCell) {
              const parts = (hotelCell.innerText || '').trim().split('\n').map(s => s.trim()).filter(Boolean);
              hotel = parts.length ? parts[parts.length - 1] : '';
            }

            // Nights: prefer data-nights attribute
            let nights = 0;
            const nightsAttr = row.getAttribute('data-nights');
            if (nightsAttr) nights = parseInt(nightsAttr, 10) || 0;
            if (!nights) {
              const nightsCell = row.querySelector('td.c') || Array.from(row.querySelectorAll('td')).find(td => /\b\d{1,2}\b/.test(td.innerText || '') && (td.innerText || '').length < 6);
              if (nightsCell) {
                const m = (nightsCell.innerText || '').match(/\d{1,2}/);
                if (m) nights = parseInt(m[0], 10);
              }
            }

            // Price: try multiple fallbacks
            let price = 0;
            const priceEl = row.querySelector('span.price') || row.querySelector('td.price') || row.querySelector('.td_price') || row.querySelector('.type_price');
            if (priceEl) {
              const attr = priceEl.getAttribute && priceEl.getAttribute('data-converted-price-number');
              if (attr) price = parseInt(attr, 10) || 0;
              else {
                const txt = (priceEl.innerText || priceEl.textContent || '').replace(/[^\d]/g, '');
                if (txt) price = parseInt(txt, 10) || 0;
              }
            } else {
              // fallback: search last td for digits
              const tds = Array.from(row.querySelectorAll('td'));
              for (let i = tds.length - 1; i >= 0; i--) {
                const txt = (tds[i].innerText || '').replace(/[^\d]/g, '');
                if (txt && txt.length >= 2) { price = parseInt(txt, 10); break; }
              }
            }

            // Check-in date
            let departure_date = new Date().toISOString().split('T')[0];
            const checkinStr = row.getAttribute('data-checkin');
            if (checkinStr && checkinStr.length === 8) {
              const year = checkinStr.substring(0, 4);
              const month = checkinStr.substring(4, 6);
              const day = checkinStr.substring(6, 8);
              departure_date = `${year}-${month}-${day}`;
            } else {
              const txt = row.innerText || '';
              const m = txt.match(/(\d{1,2}[./]\d{1,2}[./]\d{4})/);
              if (m) {
                const parts = m[1].split(/[./]/);
                const d = parts[0].padStart(2, '0');
                const mo = parts[1].padStart(2, '0');
                const y = parts[2];
                departure_date = `${y}-${mo}-${d}`;
              }
            }

            const urlEl = row.querySelector('a[href]');
            const url = urlEl ? (urlEl.getAttribute('href') || '') : '';

            if (index < 5) {
              debugRows.push({ index, title, hotel, nights, price, checkinStr: row.getAttribute('data-checkin'), hasPriceEl: !!priceEl, rowHtml: rowHtml.substring(0, 500) });
            }

            if (title && (price > 0 || url)) {
              results.push({
                title: title,
                hotel_name: hotel,
                hotel_category: 4,
                price: price,
                nights: nights || 7,
                departure_date: departure_date,
                available_seats: 5,
                hotel_rating: 0,
                inclusions: [],
                url: url
              });
            }
          } catch (e) {
            // ignore
          }
        });

        return { results, debugRows, rowsProcessed: rows.length };
      });

      console.log(`[ABK] Processed ${tours.rowsProcessed} rows`);
      console.log('[ABK] Debug rows:', JSON.stringify(tours.debugRows));
      console.log(`[ABK] Extracted ${tours.results.length} tours`);
      if (tours.results.length > 0) {
        console.log('[ABK] First tour:', JSON.stringify(tours.results[0]));
      }

      // Capture only tbody from table.res with UTF-8 preserved
      const tbodyInfo = await this.page.evaluate(() => {
        const tbody = document.querySelector('table.res tbody');
        if (!tbody) return { html: '', hasCyrillic: false, textLength: 0, textSample: '', charset: 'unknown' };
        
        // Verify Cyrillic text is present before returning
        const hasCyrillic = /[А-Яа-яЁё]/.test(tbody.textContent);
        const textLength = tbody.textContent.length;
        const textSample = tbody.textContent.substring(0, 200);
        const charset = document.characterSet || document.charset || 'unknown';
        
        // Return both raw HTML and metadata to diagnose encoding
        return {
          html: tbody.outerHTML,
          hasCyrillic,
          textLength,
          textSample,
          charset
        };
      });
      
      console.error('[ABK] Tbody diagnostic info (in Puppeteer context):');
      console.error('  hasCyrillic:', tbodyInfo.hasCyrillic);
      console.error('  textLength:', tbodyInfo.textLength);
      console.error('  charset:', tbodyInfo.charset);
      console.error('  textSample:', tbodyInfo.textSample);
      
      // Check if Cyrillic is present AFTER we get it back to Node
      const hasCyrillicAfterReturn = /[А-Яа-яЁё]/.test(tbodyInfo.html);
      console.error('[ABK] Cyrillic present after return from Puppeteer:', hasCyrillicAfterReturn);
      
      if (tbodyInfo.html.length > 0) {
        console.error('[ABK] First 500 chars of captured HTML:');
        console.error(tbodyInfo.html.substring(0, 500));
      }

      return {
        tours: tours.results,
        debug_table_html: tbodyInfo.html,
        debug_rows: tours.debugRows,
        rows_processed: tours.rowsProcessed
      };
    } catch (error) {
      console.error('[ABK] extractTours error:', error.message);
      return [];
    }
  }
}
