import puppeteer from 'puppeteer';

export class BaseParser {
  constructor(operatorName) {
    this.operatorName = operatorName;
    this.browser = null;
    this.page = null;
  }

  async initialize() {
    try {
      this.browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
        timeout: 30000,
      });
      this.page = await this.browser.newPage();
      await this.page.setViewport({ width: 1280, height: 1024 });
      await this.page.setDefaultTimeout(15000);
    } catch (error) {
      console.error(`[${this.operatorName}] Init error:`, error.message);
      throw error;
    }
  }

  async close() {
    if (this.browser) {
      await this.browser.close();
    }
  }

  async goto(url) {
    try {
      // Use  domcontentloaded which is faster than networkidle2
      await this.page.goto(url, { 
        waitUntil: 'domcontentloaded',
        timeout: 30000
      });
      await this.page.waitForTimeout(1000);
    } catch (error) {
      // Try to continue even if navigation times out
      console.warn(`[${this.operatorName}] Navigation warning: ${error.message}`);
      await this.page.waitForTimeout(500);
    }
  }

  async waitForSelector(selector, timeout = 5000) {
    try {
      return await this.page.waitForSelector(selector, { timeout });
    } catch (error) {
      console.warn(`[${this.operatorName}] Selector not found: ${selector}`);
      return null;
    }
  }

  async click(selector) {
    try {
      await this.page.click(selector);
      await this.page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {});
    } catch (error) {
      console.warn(`[${this.operatorName}] Click error on ${selector}:`, error.message);
    }
  }

  async type(selector, text) {
    try {
      await this.page.type(selector, text);
    } catch (error) {
      console.warn(`[${this.operatorName}] Type error on ${selector}:`, error.message);
    }
  }

  async select(selector, value) {
    try {
      await this.page.select(selector, value);
    } catch (error) {
      console.warn(`[${this.operatorName}] Select error on ${selector}:`, error.message);
    }
  }

  // Select value for hidden selects wrapped by "chosen" plugin.
  // Opens the chosen dropdown and selects an item.
  async selectChosen(selectSelector, value) {
    try {
      // Find the target option index first
      const targetIndex = await this.page.evaluate((sel, val) => {
        const selectEl = document.querySelector(sel);
        if (!selectEl) return -1;
        
        const options = Array.from(selectEl.options);
        let idx = options.findIndex(o => o.value == val || o.text.trim() == val);
        if (idx === -1 && !isNaN(parseInt(val))) {
          const numVal = parseInt(val);
          idx = options.findIndex((o, i) => i === numVal || o.value == String(numVal));
        }
        return idx;
      }, selectSelector, value);

      if (targetIndex < 0) {
        console.warn(`[${this.operatorName}] selectChosen: Could not find option for value: ${value}`);
        return;
      }

      console.log(`[${this.operatorName}] selectChosen: Found target index ${targetIndex} for value ${value}`);

      // Get the chosen container selector by querying the select's next sibling
      const chosenSelector = await this.page.evaluate((sel) => {
        const selectEl = document.querySelector(sel);
        if (!selectEl) return null;
        
        let parent = selectEl.parentElement;
        while (parent && !parent.querySelector('.chosen-container')) {
          parent = parent.parentElement;
        }
        
        const chosenEl = selectEl.nextElementSibling;
        if (chosenEl && chosenEl.classList && chosenEl.classList.contains('chosen-container')) {
          // Return a unique selector for the chosen container
          const classes = Array.from(chosenEl.classList).join('.');
          return `.` + classes;
        }
        
        return null;
      }, selectSelector);

      if (!chosenSelector) {
        console.warn(`[${this.operatorName}] selectChosen: Could not find chosen container`);
        return;
      }

      // Click on a.chosen-single using Puppeteer's click method
      try {
        await this.page.click(`${chosenSelector} a.chosen-single`);
        console.log(`[${this.operatorName}] selectChosen: Clicked dropdown trigger`);
      } catch (e) {
        console.warn(`[${this.operatorName}] selectChosen: Failed to click dropdown trigger: ${e.message}`);
        return;
      }

      // Wait for dropdown to fully open and render
      await this.page.waitForTimeout(1500);

      // Try to click the li element
      const liSelector = `${chosenSelector} .chosen-results li[data-option-array-index="${targetIndex}"]`;
      
      try {
        await this.page.click(liSelector);
        console.log(`[${this.operatorName}] selectChosen: Successfully clicked li element at index ${targetIndex}`);
      } catch (e) {
        console.warn(`[${this.operatorName}] selectChosen: Failed to click li by index. Error: ${e.message}`);
        
        // Fallback: try clicking by position
        const fallbackSelector = `${chosenSelector} .chosen-results li:nth-child(${targetIndex + 1})`;
        try {
          await this.page.click(fallbackSelector);
          console.log(`[${this.operatorName}] selectChosen: Successfully clicked li by position ${targetIndex}`);
        } catch (e2) {
          console.warn(`[${this.operatorName}] selectChosen: Also failed to click by position. Error: ${e2.message}`);
        }
      }

      await this.page.waitForTimeout(300);
    } catch (error) {
      console.warn(`[${this.operatorName}] selectChosen error on ${selectSelector}:`, error.message);
    }
  }

  // Set value for date inputs that may be handled by a datepicker widget. Dispatch input/change events.
  async setDateInput(selector, value) {
    try {
      await this.page.evaluate((sel, val) => {
        const el = document.querySelector(sel);
        if (!el) return;
        el.focus && el.focus();
        el.value = val;
        const inputEvt = new Event('input', { bubbles: true });
        el.dispatchEvent(inputEvt);
        const changeEvt = new Event('change', { bubbles: true });
        el.dispatchEvent(changeEvt);
        el.blur && el.blur();
      }, selector, value);
      await this.page.waitForTimeout(200);
    } catch (error) {
      console.warn(`[${this.operatorName}] setDateInput error on ${selector}:`, error.message);
    }
  }

  async evaluate(fn, ...args) {
    return await this.page.evaluate(fn, ...args);
  }

  async parse(url, filters) {
    try {
      await this.initialize();
      await this.goto(url);
      await this.fillFilters(filters);
      await this.submitSearch();
      await this.page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {});
      const tours = await this.extractTours();
      return tours;
    } catch (error) {
      console.error(`[${this.operatorName}] Parse error:`, error.message);
      return [];
    } finally {
      await this.close();
    }
  }

  // Methods to override in subclasses
  async fillFilters(filters) {
    throw new Error('fillFilters() must be implemented by subclass');
  }

  async submitSearch() {
    throw new Error('submitSearch() must be implemented by subclass');
  }

  async extractTours() {
    throw new Error('extractTours() must be implemented by subclass');
  }
}
