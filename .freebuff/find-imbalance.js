const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');

// Track depth from <main> (line 203, 0-indexed 202) through main section
let depth = 0;
const sections = [];
let lastComment = 'MAIN START';
for (let i = 202; i < 1000; i++) {
  const line = lines[i];
  if (line.includes('<!-- ===================')) lastComment = line.trim();
  const opens = (line.match(/<div[\s>]/g) || []).length;
  const closes = (line.match(/<\/div>/g) || []).length;
  const before = depth;
  depth += opens - closes;
  // If depth goes negative or drops to 0 mid-section, report
  if (depth < 0) {
    console.log('DEPTH NEGATIVE at line', i + 1, 'depth:', depth);
    console.log('Section:', lastComment);
    console.log('Line:', line.trim().substring(0, 100));
    break;
  }
  if (depth === 0 && i > 210 && i < 999) {
    console.log('DEPTH HITS ZERO at line', i + 1);
    console.log('Section:', lastComment);
    console.log('Line:', line.trim().substring(0, 100));
    // continue to see pattern
  }
  sections.push({ line: i + 1, depth, section: lastComment });
}

// Print depth at each section boundary
console.log('\nDepth at section boundaries:');
let prevSection = '';
for (const sec of sections) {
  if (sec.section !== prevSection) {
    console.log('Line', sec.line, ':', sec.section.substring(0, 60), '→ depth', sec.depth);
    prevSection = sec.section;
  }
}
