import { SelfieParser } from './parsers/selfie-parser.js';
import { AbkParser } from './parsers/abk-parser.js';
import { TravelluxeParser } from './parsers/travelluxe-parser.js';
import { KazunionParser } from './parsers/kazunion-parser.js';
import { FstravelParser } from './parsers/fstravel-parser.js';
import { CrystalBayParser } from './parsers/crystalbay-parser.js';
import { MockParser } from './parsers/mock-parser.js';
import * as fs from 'fs';
import * as path from 'path';

// Redirect console.log to stderr so stdout is clean for JSON output
const originalLog = console.log;
const originalError = console.error;
const originalWarn = console.warn;

console.log = function(...args) {
  process.stderr.write('[LOG] ' + args.join(' ') + '\n');
};

console.error = function(...args) {
  process.stderr.write('[ERROR] ' + args.join(' ') + '\n');
};

console.warn = function(...args) {
  process.stderr.write('[WARN] ' + args.join(' ') + '\n');
};


const OPERATORS = [
  {
    name: 'mock',
    url: 'http://localhost:5000/mock',
    parser: MockParser
  },
  {
    name: 'abk',
    url: 'https://b2b.abktourism.kz/search_tour',
    parser: AbkParser
  }
  // Live parsers disabled - they cause network timeouts; enable once selectors are verified and network is optimized
  /*
  ,
  {
    name: 'selfie',
    url: 'https://b2b.selfietravel.kz/search_tour',
    parser: SelfieParser
  },
  {
    name: 'travelluxe',
    url: 'https://online.travelluxe.kz/search_tour',
    parser: TravelluxeParser
  },
  {
    name: 'kazunion',
    url: 'https://online.kazunion.com/search_tour',
    parser: KazunionParser
  },
  {
    name: 'fstravel',
    url: 'https://b2b.fstravel.asia/search_tour',
    parser: FstravelParser
  },
  {
    name: 'crystalbay',
    url: 'https://booking-kz.crystalbay.com/search_tour',
    parser: CrystalBayParser
  }
  */
];

async function parseAllOperators(filters) {
  console.log('[Index] Starting parse with filters:', JSON.stringify(filters));
  const resultsByOperator = {};  // Changed: organize by operator name

  for (const operator of OPERATORS) {
    try {
      console.log(`[Index] Parsing ${operator.name}...`);
      const parserClass = operator.parser;
      const parser = new parserClass();
      
      const parseOutput = await parser.parse(operator.url, filters);

      // parseOutput may be an array of tours or an object { tours, debug_table_html }
      let tours = [];
      if (Array.isArray(parseOutput)) {
        tours = parseOutput;
      } else if (parseOutput && Array.isArray(parseOutput.tours)) {
        tours = parseOutput.tours;
        // attach debug HTML under a field on the operator (collected for final JSON)
        if (parseOutput.debug_table_html) {
          // encode to base64 so that any intermediary (PowerShell, PHP) won't corrupt UTF-8
          try {
            operator.debug_table_html_b64 = Buffer.from(parseOutput.debug_table_html, 'utf8').toString('base64');
          } catch (e) {
            console.error('[Index] Failed to base64 encode debug HTML:', e.message);
            // fallback to raw string
            operator.debug_table_html = parseOutput.debug_table_html;
          }
        }
      }

      // Add operator name to each tour
      tours.forEach(tour => {
        tour.operator = operator.name;
      });

      // Changed: store by operator name instead of flat array
      resultsByOperator[operator.name] = tours;
      console.log(`[Index] ${operator.name} completed: ${tours.length} tours`);
    } catch (error) {
      console.error(`[Index] Error parsing ${operator.name}:`, error.message);
      // Changed: return empty array for failed operators
      resultsByOperator[operator.name] = [];
    }
  }

  console.log(`[Index] Total tours parsed across all operators`);
  // Collect debug HTML per operator if present (base64-encoded strings)
  const debugTablesByOperator = {};
  for (const op of OPERATORS) {
    if (op.debug_table_html_b64) {
      debugTablesByOperator[op.name] = op.debug_table_html_b64;
    } else if (op.debug_table_html) {
      // fallback raw string (should rarely happen)
      debugTablesByOperator[op.name] = op.debug_table_html;
    }
    
    // Save raw HTML for ABK results (for later parsing)
    if (op.name === 'abk' && op.debug_table_html) {
      try {
        const debugDir = path.resolve('./debug');
        if (!fs.existsSync(debugDir)) {
          fs.mkdirSync(debugDir, { recursive: true });
        }
        const filePath = path.join(debugDir, 'abk_table_res.html');
        fs.writeFileSync(filePath, op.debug_table_html, 'utf8');
        console.log(`[Index] Saved ABK results HTML to: ${filePath}`);
      } catch (err) {
        console.warn(`[Index] Failed to save ABK HTML: ${err.message}`);
      }
    }
  }

  return { operatorsTours: resultsByOperator, debug_tables_by_operator: debugTablesByOperator };
}

async function main() {
  try {
    // Ensure UTF-8 encoding for input and output
    if (process.stdin.setEncoding) process.stdin.setEncoding('utf8');
    if (process.stdout.setEncoding) process.stdout.setEncoding('utf8');
    if (process.stderr.setEncoding) process.stderr.setEncoding('utf8');
    
    // Read input from PHP via stdin
    let inputData = '';
    
    process.stdin.on('data', chunk => {
      inputData += chunk;
    });

    process.stdin.on('end', async () => {
      try {
        const filters = JSON.parse(inputData);
        const results = await parseAllOperators(filters);

        // Output results as JSON to stdout using utf8 Buffer encoding
        // Changed: return operators keyed by name, not a flat tours array
        const output = {
          success: true,
          ...results.operatorsTours,  // Spread operator tours
          debug_tables_by_operator: results.debug_tables_by_operator || {},
          timestamp: new Date().toISOString()
        };
        
        // Ensure UTF-8 encoding is preserved through JSON serialization
        const jsonString = JSON.stringify(output);
        process.stdout.write(Buffer.from(jsonString, 'utf8').toString('utf8') + '\n');
        process.exit(0);
      } catch (parseError) {
        console.error('Input parse error:', parseError.message);
        process.stdout.write(JSON.stringify({
          success: false,
          error: 'Invalid input JSON'
        }) + '\n');
        process.exit(1);
      }
    });

    // Set timeout for the entire operation (5 minutes)
    setTimeout(() => {
      console.error('[Index] Timeout: parsing took too long');
      process.stdout.write(JSON.stringify({
        success: false,
        error: 'Parsing timeout'
      }) + '\n');
      process.exit(1);
    }, 300000);

  } catch (error) {
    console.error('[Index] Fatal error:', error.message);
    process.stdout.write(JSON.stringify({
      success: false,
      error: error.message
    }) + '\n');
    process.exit(1);
  }
}

main();
