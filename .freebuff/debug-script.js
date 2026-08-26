const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');

// Check script tags
const scripts = [...s.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)];
console.log('Script tags found:', scripts.length);
scripts.forEach((m, i) => {
  const content = m[1].trim();
  console.log(`Script ${i}: ${content.length} chars, starts with: ${content.substring(0, 80)}`);
  if (content.includes('function app')) {
    console.log('  -> Contains function app()');
  }
});

// Also check for x-data in the HTML
const xDataMatches = [...s.matchAll(/x-data="([^"]+)"/g)];
console.log('\nx-data attributes:', xDataMatches.length);
xDataMatches.forEach((m, i) => {
  console.log(`  ${i}: ${m[1].substring(0, 80)}`);
});

// Check if app is defined via Alpine.data
const alpineData = [...s.matchAll(/Alpine\.data\(['"](\w+)/g)];
console.log('\nAlpine.data registrations:', alpineData.length);
alpineData.forEach((m, i) => {
  console.log(`  ${i}: ${m[1]}`);
});
