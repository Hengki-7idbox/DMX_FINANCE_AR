const fs = require('fs');
let s = fs.readFileSync('build/preview/index.html', 'utf8');

// Fix the accuratePiutang data - divide all monetary values by 1000000
// Current data has values like 500000000, which fmtRp turns into 500,000,000,000,000
// fmtRp expects values in miliar: e.g. 0.5 for Rp 500 juta

// Replace the entire accuratePiutang array
const oldData = `accuratePiutang: [
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

const newData = `accuratePiutang: [
            { custNo:'C-001', custName:'PT. Maju Jaya', invoiceNo:'INV-2026-0847', invoiceDate:'2026-07-15', total:500, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:500, ageInvoice:112, ageDue:95, suspend:'No', creditLimit:800, termNet:30 },
            { custNo:'C-001', custName:'PT. Maju Jaya', invoiceNo:'INV-2026-0901', invoiceDate:'2026-08-01', total:200, age1_30:0, age31_60:0, age61_90:0, age91_120:200, ageOver120:0, ageInvoice:95, ageDue:78, suspend:'No', creditLimit:800, termNet:30 },
            { custNo:'C-002', custName:'PT. Sukses Abadi', invoiceNo:'INV-2026-0855', invoiceDate:'2026-06-20', total:750, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:750, ageInvoice:137, ageDue:120, suspend:'Yes', creditLimit:500, termNet:45 },
            { custNo:'C-003', custName:'PT. Makmur Sejahtera', invoiceNo:'INV-2026-0862', invoiceDate:'2026-07-25', total:320, age1_30:0, age31_60:0, age61_90:320, age91_120:0, ageOver120:0, ageInvoice:72, ageDue:55, suspend:'No', creditLimit:600, termNet:30 },
            { custNo:'C-003', custName:'PT. Makmur Sejahtera', invoiceNo:'INV-2026-0910', invoiceDate:'2026-08-10', total:180, age1_30:0, age31_60:180, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:46, ageDue:29, suspend:'No', creditLimit:600, termNet:30 },
            { custNo:'C-004', custName:'PT. Mulia Persada', invoiceNo:'INV-2026-0870', invoiceDate:'2026-08-15', total:150, age1_30:150, age31_60:0, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:11, ageDue:5, suspend:'No', creditLimit:400, termNet:15 },
            { custNo:'C-005', custName:'PT. Berkah Mandiri', invoiceNo:'INV-2026-0878', invoiceDate:'2026-05-10', total:450, age1_30:0, age31_60:0, age61_90:0, age91_120:0, ageOver120:450, ageInvoice:158, ageDue:141, suspend:'Yes', creditLimit:300, termNet:30 },
            { custNo:'C-006', custName:'PT. Sentosa Jaya', invoiceNo:'INV-2026-0885', invoiceDate:'2026-08-05', total:280, age1_30:0, age31_60:280, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:51, ageDue:34, suspend:'No', creditLimit:500, termNet:30 },
            { custNo:'C-007', custName:'PT. Gemilang Perkasa', invoiceNo:'INV-2026-0892', invoiceDate:'2026-07-01', total:620, age1_30:0, age31_60:0, age61_90:620, age91_120:0, ageOver120:0, ageInvoice:86, ageDue:69, suspend:'No', creditLimit:700, termNet:45 },
            { custNo:'C-008', custName:'PT. Nusantara Teknik', invoiceNo:'INV-2026-0899', invoiceDate:'2026-08-20', total:95, age1_30:95, age31_60:0, age61_90:0, age91_120:0, ageOver120:0, ageInvoice:6, ageDue:1, suspend:'No', creditLimit:200, termNet:15 },
        ],`;

if (s.includes(oldData)) {
  s = s.replace(oldData, newData);
  fs.writeFileSync('build/preview/index.html', s);
  console.log('Fixed accuratePiutang data values (divided by 1000000)');
} else {
  console.log('Old data pattern not found, trying partial match...');
  // Try to find and replace just the total values
  const regex = /accuratePiutang: \[([\s\S]*?)\],/;
  const match = s.match(regex);
  if (match) {
    let newDataBlock = match[1];
    // Replace all large numbers (dividing by 1000000)
    newDataBlock = newDataBlock.replace(/total:(\d+)/g, (m, num) => `total:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/age1_30:(\d+)/g, (m, num) => `age1_30:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/age31_60:(\d+)/g, (m, num) => `age31_60:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/age61_90:(\d+)/g, (m, num) => `age61_90:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/age91_120:(\d+)/g, (m, num) => `age91_120:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/ageOver120:(\d+)/g, (m, num) => `ageOver120:${Math.round(parseInt(num)/1000000)}`);
    newDataBlock = newDataBlock.replace(/creditLimit:(\d+)/g, (m, num) => `creditLimit:${Math.round(parseInt(num)/1000000)}`);
    s = s.replace(match[0], `accuratePiutang: [${newDataBlock}],`);
    fs.writeFileSync('build/preview/index.html', s);
    console.log('Fixed via regex replacement');
  } else {
    console.log('ERROR: Could not find accuratePiutang data');
  }
}
