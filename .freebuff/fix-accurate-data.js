const fs = require('fs');
let s = fs.readFileSync('build/preview/index.html', 'utf8');

// 1. Add accurateSearch, accuratePiutang data, and accurateLastImport after the accurateImportHistory array
// Find the end of accurateImportHistory array
const histEnd = s.indexOf('],', s.indexOf('accurateImportHistory'));
if (histEnd === -1) { console.log('ERROR: Could not find accurateImportHistory end'); process.exit(1); }

const insertAfterHist = histEnd + 2; // after "],"

const newData = `
        accurateSearch: '',
        accurateLastImport: '2026-08-22',
        accuratePiutang: [
            { custNo:'C-001', custName:'PT. Maju Jaya', invoiceNo:'INV-2026-0847', invoiceDate:'2026-07-15', total:500000000, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:500000000, ageInvoice:112, ageDue:95, suspend:'No', creditLimit:800000000, termNet:30 },
            { custNo:'C-001', custName:'PT. Maju Jaya', invoiceNo:'INV-2026-0901', invoiceDate:'2026-08-01', total:200000000, age1_30:0, age31_60:0, age61_90:0, age91_120:200000000, ageOver120:0, ageInvoice:95, ageDue:78, suspend:'No', creditLimit:800000000, termNet:30 },
            { custNo:'C-002', custName:'PT. Sukses Abadi', invoiceNo:'INV-2026-0855', invoiceDate:'2026-06-20', total:750000000, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:750000000, ageInvoice:137, ageDue:120, suspend:'Yes', creditLimit:500000000, termNet:45 },
            { custNo:'C-003', custName:'PT. Makmur Sejahtera', invoiceNo:'INV-2026-0862', invoiceDate:'2026-07-25', total:320000000, age1_30:0, age31_60:0, age61_90:320000000, age91_120:0, ageOver120:0, ageInvoice:72, ageDue:55, suspend:'No', creditLimit:600000000, termNet:30 },
            { custNo:'C-003', custName:'PT. Makmur Sejahtera', invoiceNo:'INV-2026-0910', invoiceDate:'2026-08-10', total:180000000, age1_30:0, age31_60:180000000, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:46, ageDue:29, suspend:'No', creditLimit:600000000, termNet:30 },
            { custNo:'C-004', custName:'PT. Mulia Persada', invoiceNo:'INV-2026-0870', invoiceDate:'2026-08-15', total:150000000, age1_30:150000000, age31_60:0, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:11, ageDue:5, suspend:'No', creditLimit:400000000, termNet:15 },
            { custNo:'C-005', custName:'PT. Berkah Mandiri', invoiceNo:'INV-2026-0878', invoiceDate:'2026-05-10', total:450000000, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:450000000, ageInvoice:158, ageDue:141, suspend:'Yes', creditLimit:300000000, termNet:30 },
            { custNo:'C-006', custName:'PT. Sentosa Jaya', invoiceNo:'INV-2026-0885', invoiceDate:'2026-08-05', total:280000000, age1_30:0, age31_60:280000000, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:51, ageDue:34, suspend:'No', creditLimit:500000000, termNet:30 },
            { custNo:'C-007', custName:'PT. Gemilang Perkasa', invoiceNo:'INV-2026-0892', invoiceDate:'2026-07-01', total:620000000, age1_30:0, age31_60:0, age61_90:620000000, age91_120:0, ageOver120:0, ageInvoice:86, ageDue:69, suspend:'No', creditLimit:700000000, termNet:45 },
            { custNo:'C-008', custName:'PT. Nusantara Teknik', invoiceNo:'INV-2026-0899', invoiceDate:'2026-08-20', total:95000000, age1_30:95000000, age31_60:0, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:6, ageDue:1, suspend:'No', creditLimit:200000000, termNet:15 },
        ],`;

s = s.slice(0, insertAfterHist) + newData + s.slice(insertAfterHist);

// 2. Add filteredAccuratePiutang as a getter
// Find the get filteredCustomers line and add after it
const custGetter = s.indexOf('get filteredCustomers()');
if (custGetter === -1) { console.log('ERROR: Could not find filteredCustomers getter'); process.exit(1); }

// Find the closing brace+comma of this getter
let braceCount = 0;
let getterEnd = -1;
for (let i = custGetter; i < s.length; i++) {
  if (s[i] === '{') braceCount++;
  if (s[i] === '}') { braceCount--; if (braceCount === 0) { getterEnd = i + 1; break; } }
}

const filteredPiutangGetter = `
        get filteredAccuratePiutang() {
            if (!this.accurateSearch) return this.accuratePiutang;
            const q = this.accurateSearch.toLowerCase();
            return this.accuratePiutang.filter(p => p.custNo.toLowerCase().includes(q) || p.custName.toLowerCase().includes(q) || p.invoiceNo.toLowerCase().includes(q));
        },`;

s = s.slice(0, getterEnd) + filteredPiutangGetter + s.slice(getterEnd);

fs.writeFileSync('build/preview/index.html', s);
console.log('Added accuratePiutang data + filteredAccuratePiutang getter');

// Verify
const s2 = fs.readFileSync('build/preview/index.html', 'utf8');
console.log('accuratePiutang occurrences:', (s2.match(/accuratePiutang/g) || []).length);
console.log('filteredAccuratePiutang occurrences:', (s2.match(/filteredAccuratePiutang/g) || []).length);
