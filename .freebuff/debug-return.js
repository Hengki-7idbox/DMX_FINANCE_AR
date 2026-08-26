const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const start = s.indexOf('function app()');
const lines = s.split('\n');
let depth = 0, returnStart = -1, funcStartLine = -1;

// Find function app() line
for (let i = 0; i < lines.length; i++) {
  if (lines[i].includes('function app()')) { funcStartLine = i; break; }
}

// Find 'return {' line
for (let i = funcStartLine; i < lines.length; i++) {
  if (lines[i].includes('return {')) { returnStart = i; break; }
}

console.log('func app() at line:', funcStartLine + 1);
console.log('return { at line:', returnStart + 1);

// Track depth from return { line
depth = 0;
let endLine = -1;
for (let i = returnStart; i < lines.length; i++) {
  for (let j = 0; j < lines[i].length; j++) {
    if (lines[i][j] === '{') depth++;
    if (lines[i][j] === '}') {
      depth--;
      if (depth === 0) {
        endLine = i;
        console.log('Return block ends at line:', endLine + 1);
        console.log('Last 3 lines of return block:');
        for (let k = endLine - 2; k <= endLine; k++) {
          console.log((k + 1) + ':', lines[k]);
        }
        // Show what's after
        console.log('\nAfter return block:');
        for (let k = endLine + 1; k <= Math.min(endLine + 5, lines.length - 1); k++) {
          console.log((k + 1) + ':', lines[k]);
        }
        process.exit(0);
      }
    }
  }
}
console.log('Could not find matching } for return block!');
