const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');
const start = lines.findIndex(l => l.includes('function app()'));
const funcText = lines.slice(start).join('\n');
const retStart = funcText.indexOf('return {');
const retText = funcText.substring(retStart);

// Track brace depth per line
let depth = 0;
const retLines = retText.split('\n');
for (let i = 0; i < retLines.length; i++) {
  let lineDepth = 0;
  for (const c of retLines[i]) {
    if (c === '{') lineDepth++;
    if (c === '}') lineDepth--;
  }
  depth += lineDepth;
  if (depth < 0) {
    console.log('DEPTH GOES NEGATIVE at return block line', i + 1, '(file line', start + i + 1 + ')');
    console.log('Content:', retLines[i].trim());
    console.log('Depth before:', depth - lineDepth, 'after:', depth);
    // Show context
    for (let j = Math.max(0, i - 5); j <= Math.min(retLines.length - 1, i + 5); j++) {
      console.log((j === i ? '>>>' : '   '), (start + j + 1) + ':', retLines[j].substring(0, 120));
    }
    break;
  }
}
if (depth >= 0) {
  console.log('Final depth:', depth);
  // Find where excess closing brace is
  depth = 0;
  for (let i = 0; i < retLines.length; i++) {
    let lineDepth = 0;
    for (const c of retLines[i]) {
      if (c === '{') lineDepth++;
      if (c === '}') lineDepth--;
    }
    if (depth === 0 && lineDepth < 0) {
      console.log('EXTRA } at return block line', i + 1, '(file line', start + i + 1 + ')');
      console.log('Content:', retLines[i].trim());
      for (let j = Math.max(0, i - 3); j <= Math.min(retLines.length - 1, i + 3); j++) {
        console.log((j === i ? '>>>' : '   '), (start + j + 1) + ':', retLines[j].substring(0, 120));
      }
    }
    depth += lineDepth;
  }
}
