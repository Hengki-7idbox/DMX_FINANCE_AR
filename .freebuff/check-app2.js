const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const start = s.indexOf('function app()');
const braceStart = s.indexOf('{', start);
let depth = 0, end = -1;
for (let i = braceStart; i < s.length; i++) {
  if (s[i] === '{') depth++;
  if (s[i] === '}') { depth--; if (depth === 0) { end = i + 1; break; } }
}
const appFunc = s.substring(start, end);

// Check if it returns an object properly
const lines = appFunc.split('\n');
console.log('First 3 lines:');
for (let i = 0; i < 3; i++) console.log(i+1 + ':', lines[i]);
console.log('...');
console.log('Last 10 lines:');
for (let i = lines.length - 10; i < lines.length; i++) {
  console.log((i+1) + ':', lines[i]);
}

// Find 'return {' and '}' after it
const returnIdx = appFunc.indexOf('return {');
if (returnIdx > -1) {
  console.log('\nreturn { found at char', returnIdx);
  // count depth from return {
  const afterReturn = appFunc.substring(returnIdx);
  depth = 0;
  let rEnd = -1;
  for (let i = 0; i < afterReturn.length; i++) {
    if (afterReturn[i] === '{') depth++;
    if (afterReturn[i] === '}') { depth--; if (depth === 0) { rEnd = i; break; } }
  }
  const returnBlock = afterReturn.substring(0, rEnd + 1);
  console.log('Return block lines:', returnBlock.split('\n').length);
  console.log('Last 5 lines of return block:');
  const rbLines = returnBlock.split('\n');
  for (let i = rbLines.length - 5; i < rbLines.length; i++) {
    console.log((i+1) + ':', rbLines[i]);
  }
}
