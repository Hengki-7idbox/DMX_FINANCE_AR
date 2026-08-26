const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');
const start = lines.findIndex(l => l.includes('function app()'));
const funcLines = lines.slice(start);

// Find 'return {' at depth 0 within the function
let depth = 0, returnStartLine = -1;
for (let i = 0; i < funcLines.length; i++) {
  for (const c of funcLines[i]) {
    if (c === '{') depth++;
    if (c === '}') depth--;
  }
  if (depth === 0 && funcLines[i].includes('return {')) {
    returnStartLine = i;
    break;
  }
}

console.log('return { found at func-relative line:', returnStartLine + 1, '(file line:', start + returnStartLine + 1, ')');

// Now count braces from return { line to find where return block ends
depth = 0;
let returnEndLine = -1;
for (let i = returnStartLine; i < funcLines.length; i++) {
  for (const c of funcLines[i]) {
    if (c === '{') depth++;
    if (c === '}') depth--;
  }
  if (depth === 0) {
    returnEndLine = i;
    break;
  }
}
console.log('Return block ends at func-relative line:', returnEndLine + 1, '(file line:', start + returnEndLine + 1, ')');

// Check if accuratePiutang is inside the return block
const returnBlock = funcLines.slice(returnStartLine, returnEndLine + 1).join('\n');
const hasAccuratePiutang = returnBlock.includes('accuratePiutang:');
const hasFilteredAP = returnBlock.includes('filteredAccuratePiutang');
const hasFmtRp = returnBlock.includes('fmtRp');
const hasSidebarLocked = returnBlock.includes('sidebarLocked:');
const hasDarkMode = returnBlock.includes('darkMode:');

console.log('\nIn return block:');
console.log('  accuratePiutang:', hasAccuratePiutang);
console.log('  filteredAccuratePiutang:', hasFilteredAP);
console.log('  fmtRp:', hasFmtRp);
console.log('  sidebarLocked:', hasSidebarLocked);
console.log('  darkMode:', hasDarkMode);
