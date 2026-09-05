const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');

// Check div balance in reminders template (lines 483-638, 0-indexed 482-637)
let depth = 0;
const start = 482, end = 638; // 0-indexed
for (let i = start; i < end; i++) {
  const line = lines[i];
  const opens = (line.match(/<div[\s>]/g) || []).length;
  const closes = (line.match(/<\/div>/g) || []).length;
  depth += opens - closes;
  if (i === end - 1) {
    console.log('Div balance at end of reminders section:', depth);
  }
}

// Check template open/close balance
let tDepth = 0;
for (let i = start; i < end; i++) {
  const line = lines[i];
  tDepth += (line.match(/<template[\s>]/g) || []).length;
  tDepth -= (line.match(/<\/template>/g) || []).length;
}
console.log('Template balance:', tDepth);

// Also check whole main section balance (203-1000)
let mDepth = 0;
for (let i = 202; i < 1000; i++) {
  const line = lines[i];
  mDepth += (line.match(/<div[\s>]/g) || []).length;
  mDepth -= (line.match(/<\/div>/g) || []).length;
}
console.log('Div balance in main section:', mDepth);
