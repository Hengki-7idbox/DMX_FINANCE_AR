const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');
const start = lines.findIndex(l => l.includes('function app()'));
let depth = 0, started = false, endLine = -1;
for (let i = start; i < lines.length; i++) {
  for (const c of lines[i]) {
    if (c === '{') { depth++; started = true; }
    if (c === '}') {
      depth--;
      if (started && depth === 0) { endLine = i; break; }
    }
  }
  if (endLine > 0) break;
}
console.log('app() starts at line', start + 1, 'ends at line', endLine + 1);

// Now try to extract and eval just the return object
const funcText = lines.slice(start, endLine + 1).join('\n');
const retStart = funcText.indexOf('return {');
const retText = funcText.substring(retStart);
console.log('return block starts at relative char', retStart);

// Count braces in the return block
let d = 0;
for (const c of retText) {
  if (c === '{') d++;
  if (c === '}') d--;
}
console.log('Brace balance in return block:', d, d === 0 ? 'OK' : 'MISMATCH!');

// Find first unmatched brace
if (d !== 0) {
  d = 0;
  for (let i = 0; i < retText.length; i++) {
    if (retText[i] === '{') d++;
    if (retText[i] === '}') d--;
    if (d < 0) {
      console.log('First unmatched } at relative char', i);
      console.log('Context:', retText.substring(Math.max(0, i - 50), i + 50));
      break;
    }
  }
}

// Check around accuratePiutang specifically
const apIdx = retText.indexOf('accuratePiutang');
if (apIdx > -1) {
  console.log('\nAround accuratePiutang:');
  const context = retText.substring(apIdx - 100, apIdx + 200);
  console.log(context);
}
