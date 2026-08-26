const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');

// Extract app() function
const start = s.indexOf('function app()');
const braceStart = s.indexOf('{', start);
let depth = 0, end = -1;
for (let i = braceStart; i < s.length; i++) {
  if (s[i] === '{') depth++;
  if (s[i] === '}') { depth--; if (depth === 0) { end = i + 1; break; } }
}
const appFunc = s.substring(start, end);
console.log('app() function length:', appFunc.length);
console.log('Lines:', appFunc.split('\n').length);

// Try to parse it
try {
  new Function('return ' + appFunc);
  console.log('app() syntax OK');
} catch(e) {
  console.log('SYNTAX ERROR in app():', e.message);
  // Find the line
  const lines = appFunc.split('\n');
  const m = e.message.match(/position (\d+)/);
  if (m) {
    let pos = parseInt(m[1]);
    let count = 0;
    for (let i = 0; i < lines.length; i++) {
      count += lines[i].length + 1;
      if (count >= pos) { console.log('Error near line', i+1, ':', lines[i].substring(0, 100)); break; }
    }
  }
}

// Also check around filteredCustomers and filteredAccuratePiutang
const lines = s.split('\n');
lines.forEach((l, i) => {
  if (l.includes('filteredCustomers') || l.includes('filteredAccuratePiutang')) {
    console.log((i+1) + ': ' + l.trim().substring(0, 120));
  }
});
