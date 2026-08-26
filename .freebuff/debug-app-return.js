const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');

// Extract script content
const scriptMatch = s.match(/<script>([\s\S]*?)<\/script>/);
if (!scriptMatch) { console.log('No script found'); process.exit(1); }
const script = scriptMatch[1];

// Find app function
const appStart = script.indexOf('function app()');
if (appStart === -1) { console.log('No app() found'); process.exit(1); }

// Extract from function start to end of return block
const afterApp = script.substring(appStart);
// Find the matching closing brace for the function
let depth = 0, funcEnd = -1;
let started = false;
for (let i = 0; i < afterApp.length; i++) {
  if (afterApp[i] === '{') { depth++; started = true; }
  if (afterApp[i] === '}') {
    depth--;
    if (started && depth === 0) { funcEnd = i + 1; break; }
  }
}

const appFunc = afterApp.substring(0, funcEnd);
console.log('app() function length:', appFunc.length);
console.log('Lines:', appFunc.split('\n').length);

// Try to evaluate
try {
  const fn = new Function(appFunc + '\nreturn app();');
  const result = fn();
  console.log('\nResult type:', typeof result);
  if (typeof result === 'function') {
    console.log('PROBLEM: app() returns a function, not an object!');
    console.log('This means the return {} block closes early');
  } else if (typeof result === 'object') {
    console.log('OK: app() returns an object');
    console.log('Keys count:', Object.keys(result).length);
    console.log('Has accuratePiutang:', 'accuratePiutang' in result);
    console.log('Has darkMode:', 'darkMode' in result);
    console.log('Has sidebarLocked:', 'sidebarLocked' in result);
    console.log('Has fmtRp:', 'fmtRp' in result);
  }
} catch(e) {
  console.log('ERROR:', e.message);
}
