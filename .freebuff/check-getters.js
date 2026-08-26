const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');

// Count get accessors
const gets = [...s.matchAll(/get (\w+)\(\)/g)];
console.log('get accessors found:', gets.length, gets.map(g => g[1]));

// Check if there are any syntax errors in the x-data object
// by trying to eval just the function
const start = s.indexOf('function app()');
const lines = s.split('\n');
let braceDepth = 0, funcEndLine = -1;
let inFunc = false;
for (let i = 0; i < lines.length; i++) {
  if (lines[i].includes('function app()')) inFunc = true;
  if (inFunc) {
    for (const ch of lines[i]) {
      if (ch === '{') braceDepth++;
      if (ch === '}') braceDepth--;
    }
    if (braceDepth === 0 && inFunc && i > 0) {
      funcEndLine = i;
      break;
    }
  }
}
console.log('app() ends at line:', funcEndLine + 1);

// Check line counts
console.log('Total lines:', lines.length);
