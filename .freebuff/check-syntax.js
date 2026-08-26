const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const m = s.match(/<script>([\s\S]*?)<\/script>/);
if (m) {
  try {
    new Function(m[1]);
    console.log('JS syntax OK');
  } catch (e) {
    console.log('SYNTAX ERROR:', e.message);
    // Find approximate line
    const lines = m[1].split('\n');
    const lineNum = parseInt((e.message.match(/position (\d+)/) || [])[1]) || -1;
    console.log('Script lines:', lines.length);
  }
} else {
  console.log('No script tag found');
}
