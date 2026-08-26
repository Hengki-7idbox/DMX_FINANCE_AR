const fs = require('fs');
const s = fs.readFileSync('build/preview/index.html', 'utf8');
const scripts = [...s.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)];
const appScript = scripts[5][1]; // Script 5 contains app()

// Try evaluating
try {
  const fn = new Function(appScript + '\nconst __result = app();\nreturn typeof __result;');
  const type = fn();
  console.log('app() returns:', type);
  
  if (type === 'function') {
    console.log('PROBLEM: app() returns a function');
    // Try to get the object
    const fn2 = new Function(appScript + '\nconst __result = app();\nreturn Object.keys(__result);');
    const keys = fn2();
    console.log('Function keys:', keys);
  } else if (type === 'object') {
    const fn2 = new Function(appScript + '\nconst __result = app();\nreturn { type: typeof __result, keys: Object.keys(__result).slice(0, 20), hasAccuratePiutang: "accuratePiutang" in __result, hasFmtRp: "fmtRp" in __result };');
    const result = fn2();
    console.log('Result:', JSON.stringify(result, null, 2));
  }
} catch(e) {
  console.log('ERROR:', e.message);
  // Try to narrow it down
  const lines = appScript.split('\n');
  console.log('Script has', lines.length, 'lines');
}
