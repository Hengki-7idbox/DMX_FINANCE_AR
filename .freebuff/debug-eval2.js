const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const scripts = [...s.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)];
const appScript = scripts[5][1];

// Mock browser APIs
const mockCode = `
  var localStorage = { getItem: () => 'false', setItem: () => {} };
  var document = { documentElement: { classList: { toggle: () => {} } } };
  var setTimeout = (fn, ms) => fn();
  var Chart = function() { return { destroy: () => {} }; };
  var lucide = { createIcons: () => {} };
  var console = { log: () => {} };
`;

try {
  const fn = new Function(mockCode + appScript + '\nreturn app();');
  const result = fn();
  console.log('Result type:', typeof result);
  console.log('Keys count:', Object.keys(result).length);
  console.log('Has accuratePiutang:', 'accuratePiutang' in result);
  console.log('Has darkMode:', 'darkMode' in result);
  console.log('Has sidebarLocked:', 'sidebarLocked' in result);
  console.log('Has fmtRp:', 'fmtRp' in result);
  console.log('Has filteredCustomers:', 'filteredCustomers' in result);
  console.log('Has filteredAccuratePiutang:', 'filteredAccuratePiutang' in result);
  console.log('\nFirst 10 keys:', Object.keys(result).slice(0, 10));
  console.log('All keys:', Object.keys(result).join(', '));
} catch(e) {
  console.log('ERROR:', e.message);
  // Find line
  const lines = appScript.split('\n');
  const m = e.stack.match(/<anonymous>:(\d+):(\d+)/);
  if (m) {
    const lineNum = parseInt(m[1]);
    console.log('Error at script line:', lineNum);
    console.log('Content:', lines[lineNum - 1]?.substring(0, 120));
  }
}
