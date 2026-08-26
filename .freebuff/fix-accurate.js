const fs = require('fs');
let s = fs.readFileSync('build/preview/index.html', 'utf8');
const lines = s.split('\n');

// Find the template start and end
let startIdx = -1, endIdx = -1;
for (let i = 0; i < lines.length; i++) {
  if (lines[i].includes("page==='import-accurate'") && startIdx === -1) {
    startIdx = i - 1; // <template line
  }
  if (startIdx > 0 && lines[i].trim() === '</template>' && i > startIdx) {
    endIdx = i; // </template> line
    break;
  }
}

console.log('Found template at lines', startIdx + 1, 'to', endIdx + 1);

const newTemplate = `<template x-if="page==='import-accurate'">
<div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div><h1 class="text-xl font-bold">Import Laporan Accurate</h1><p class="text-sm text-gray-500 dark:text-gray-400">Data piutang dari software Accurate</p></div>
        <div class="flex gap-2">
            <button @click="showClearAccurateModal=true" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-600/30 rounded-lg text-sm font-medium"><i data-lucide="trash-2" class="w-4 h-4"></i> Clear Data</button>
            <button @click="showAccurateImportModal=true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"><i data-lucide="upload" class="w-4 h-4"></i> Import Baru</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><p class="text-sm text-gray-500 dark:text-gray-400">Total Invoice</p><p class="text-2xl font-bold mt-1" x-text="accuratePiutang.length"></p></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><p class="text-sm text-gray-500 dark:text-gray-400">Total Outstanding</p><p class="text-2xl font-bold mt-1 text-orange-500" x-text="fmtRp(accuratePiutang.reduce((s,p)=>s+p.total,0))"></p></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><p class="text-sm text-gray-500 dark:text-gray-400">Overdue (&gt;90)</p><p class="text-2xl font-bold mt-1 text-red-500" x-text="accuratePiutang.filter(p=>p.ageInvoice>90).length+' Invoice'"></p></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><p class="text-sm text-gray-500 dark:text-gray-400">Last Import</p><p class="text-lg font-bold mt-1" x-text="accurateLastImport||'-'"></p></div>
    </div>

    <!-- Data Piutang Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold">Data Piutang</h3>
            <input type="text" x-model="accurateSearch" placeholder="Cari customer/invoice..." class="bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-1.5 text-sm w-64 focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-100 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-3 text-left">Cust No</th>
                        <th class="px-3 py-3 text-left">Customer Name</th>
                        <th class="px-3 py-3 text-left">Invoice No</th>
                        <th class="px-3 py-3 text-left">Invoice Date</th>
                        <th class="px-3 py-3 text-right">Total Prime Curr</th>
                        <th class="px-3 py-3 text-right">1-30</th>
                        <th class="px-3 py-3 text-right">31-60</th>
                        <th class="px-3 py-3 text-right">61-90</th>
                        <th class="px-3 py-3 text-right">91-120</th>
                        <th class="px-3 py-3 text-right">&gt;120</th>
                        <th class="px-3 py-3 text-center">Age Invoice</th>
                        <th class="px-3 py-3 text-center">Age Due</th>
                        <th class="px-3 py-3 text-center">Suspend</th>
                        <th class="px-3 py-3 text-right">Credit Limit</th>
                        <th class="px-3 py-3 text-center">Term Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    <template x-for="p in filteredAccuratePiutang" :key="p.invoiceNo">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2.5 font-mono" x-text="p.custNo"></td>
                            <td class="px-3 py-2.5 font-medium" x-text="p.custName"></td>
                            <td class="px-3 py-2.5 font-mono" x-text="p.invoiceNo"></td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400" x-text="p.invoiceDate"></td>
                            <td class="px-3 py-2.5 text-right font-medium" x-text="fmtRp(p.total)"></td>
                            <td class="px-3 py-2.5 text-right" :class="p.age1_30>0?'text-yellow-600 dark:text-yellow-400':'text-gray-400'" x-text="p.age1_30>0?fmtRp(p.age1_30):'-'"></td>
                            <td class="px-3 py-2.5 text-right" :class="p.age31_60>0?'text-orange-600 dark:text-orange-400':'text-gray-400'" x-text="p.age31_60>0?fmtRp(p.age31_60):'-'"></td>
                            <td class="px-3 py-2.5 text-right" :class="p.age61_90>0?'text-red-500':'text-gray-400'" x-text="p.age61_90>0?fmtRp(p.age61_90):'-'"></td>
                            <td class="px-3 py-2.5 text-right" :class="p.age91_120>0?'text-red-600':'text-gray-400'" x-text="p.age91_120>0?fmtRp(p.age91_120):'-'"></td>
                            <td class="px-3 py-2.5 text-right font-medium" :class="p.ageOver120>0?'text-red-700 dark:text-red-400':'text-gray-400'" x-text="p.ageOver120>0?fmtRp(p.ageOver120):'-'"></td>
                            <td class="px-3 py-2.5 text-center"><span class="px-2 py-0.5 rounded-full text-xs" :class="p.ageInvoice<=30?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':p.ageInvoice<=60?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':p.ageInvoice<=90?'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400':'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'" x-text="p.ageInvoice+'h'"></span></td>
                            <td class="px-3 py-2.5 text-center"><span class="px-2 py-0.5 rounded-full text-xs" :class="p.ageDue<=30?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':p.ageDue<=60?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':p.ageDue<=90?'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400':'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'" x-text="p.ageDue+'h'"></span></td>
                            <td class="px-3 py-2.5 text-center"><span class="px-2 py-0.5 rounded-full text-xs" :class="p.suspend==='Yes'?'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400':'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'" x-text="p.suspend"></span></td>
                            <td class="px-3 py-2.5 text-right text-gray-500 dark:text-gray-400" x-text="fmtRp(p.creditLimit)"></td>
                            <td class="px-3 py-2.5 text-center text-gray-500 dark:text-gray-400" x-text="p.termNet+'h'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
</template>`;

const newLines = [...lines.slice(0, startIdx), newTemplate, ...lines.slice(endIdx + 1)];
fs.writeFileSync('build/preview/index.html', newLines.join('\n'));
console.log('Replaced lines', startIdx + 1, '-', endIdx + 1, 'with new Data Piutang template');
console.log('Total lines:', newLines.length);

// Verify
const s2 = fs.readFileSync('build/preview/index.html', 'utf8');
const matches = [...s2.matchAll(/page==='import-accurate'/g)];
console.log('import-accurate count:', matches.length);
