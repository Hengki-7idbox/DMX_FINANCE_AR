<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\InvoiceExclusion;
use App\Models\EmailTemplate;
use App\Models\WaTemplate;
use App\Models\ScheduledTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@dmx.co.id',
            'password' => Hash::make('admin'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create(['name' => 'Finance Manager', 'email' => 'fm@dmx.co.id', 'password' => Hash::make('password'), 'role' => 'finance_manager', 'is_active' => true]);
        User::create(['name' => 'AR Accountant', 'email' => 'ar@dmx.co.id', 'password' => Hash::make('password'), 'role' => 'ar_accountant', 'is_active' => true]);
        User::create(['name' => 'AR Collector', 'email' => 'collector@dmx.co.id', 'password' => Hash::make('password'), 'role' => 'ar_collector', 'is_active' => true]);
        User::create(['name' => 'Sales Manager', 'email' => 'sales@dmx.co.id', 'password' => Hash::make('password'), 'role' => 'sales_manager', 'is_active' => true]);
        User::create(['name' => 'Viewer', 'email' => 'viewer@dmx.co.id', 'password' => Hash::make('password'), 'role' => 'viewer', 'is_active' => true]);

        // Customers
        $customers = [
            ['code' => 'C-001', 'name' => 'PT. Maju Jaya', 'region' => 'Jakarta', 'credit_limit' => 500000000, 'risk_profile' => 'safe'],
            ['code' => 'C-002', 'name' => 'PT. Sukses Abadi', 'region' => 'Surabaya', 'credit_limit' => 300000000, 'risk_profile' => 'caution'],
            ['code' => 'C-003', 'name' => 'PT. Makmur Sejahtera', 'region' => 'Bandung', 'credit_limit' => 200000000, 'risk_profile' => 'safe'],
            ['code' => 'C-004', 'name' => 'PT. Berkah Mandiri', 'region' => 'Semarang', 'credit_limit' => 150000000, 'risk_profile' => 'risk'],
            ['code' => 'C-005', 'name' => 'PT. Nusantara Trading', 'region' => 'Jakarta', 'credit_limit' => 750000000, 'risk_profile' => 'safe'],
        ];

        $customerModels = collect($customers)->map(fn($c) => Customer::create(array_merge($c, ['sales_rep_id' => $admin->id])));

        // Invoices
        $invoiceData = [
            ['invoice_number' => 'INV-2026-0847', 'customer_id' => 1, 'amount' => 45500000, 'status' => 'PENDING', 'days_ago' => 7],
            ['invoice_number' => 'INV-2026-0832', 'customer_id' => 2, 'amount' => 128700000, 'status' => 'SENT', 'days_ago' => 52],
            ['invoice_number' => 'INV-2026-0819', 'customer_id' => 3, 'amount' => 67200000, 'status' => 'PARTIAL', 'days_ago' => 93],
            ['invoice_number' => 'INV-2026-0805', 'customer_id' => 4, 'amount' => 23400000, 'status' => 'SETTLED', 'days_ago' => 30],
            ['invoice_number' => 'INV-2026-0798', 'customer_id' => 5, 'amount' => 89200000, 'status' => 'PENDING', 'days_ago' => 15],
        ];

        collect($invoiceData)->each(fn($inv) => Invoice::create([
            'invoice_number' => $inv['invoice_number'],
            'customer_id' => $inv['customer_id'],
            'invoice_date' => now()->subDays($inv['days_ago'] + 30)->toDateString(),
            'due_date' => now()->subDays($inv['days_ago'])->toDateString(),
            'amount' => $inv['amount'],
            'status' => $inv['status'],
        ]));

        // Invoice Exclusions
        InvoiceExclusion::create([
            'invoice_number' => 'INV-2026-0819',
            'customer_name' => 'PT. Makmur Sejahtera',
            'original_amount' => 67200000,
            'reason_code' => 'DUPLICATE',
            'excluded_by' => 'Hengki',
            'excluded_date' => now()->subDays(5),
            'status' => 'ACTIVE',
        ]);

        // Email Templates
        EmailTemplate::create([
            'name' => 'Overdue Reminder',
            'subject' => 'Pengingat Pembayaran - {{invoice_number}}',
            'body' => 'Yth. {{customer_name}},\n\nInvoice {{invoice_number}} sebesar {{amount}} telah jatuh tempo. Mohon segera melakukan pembayaran.\n\nTerima kasih.',
            'variables' => ['invoice_number', 'customer_name', 'amount'],
        ]);

        // WA Templates
        WaTemplate::create([
            'name' => 'Quick Reminder',
            'body' => 'Halo {{customer_name}}, ini pengingat untuk invoice {{invoice_number}} sebesar {{amount}} yang telah jatuh tempo. Terima kasih.',
            'variables' => ['invoice_number', 'customer_name', 'amount'],
        ]);

        // Scheduled Tasks
        ScheduledTask::create([
            'task_name' => 'Daily Reminder Check',
            'task_type' => 'reminder',
            'schedule' => '0 9 * * *',
            'is_active' => true,
        ]);
    }
}
