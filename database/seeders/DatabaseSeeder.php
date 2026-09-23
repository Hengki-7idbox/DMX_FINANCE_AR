<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\PaymentTerm;
use App\Models\BankAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $roles = ['super_admin', 'finance_ar', 'collector', 'manager', 'viewer', 'auditor'];
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        // Create Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin',
            'password' => Hash::make('admin'),
        ]);
        $admin->assignRole('super_admin');

        // Create Customer Groups
        $groups = [
            ['group_code' => 'GRP-001', 'group_name' => 'Distributor', 'description' => 'Distributor products'],
            ['group_code' => 'GRP-002', 'group_name' => 'Corporate', 'description' => 'Corporate clients'],
            ['group_code' => 'GRP-003', 'group_name' => 'Retail', 'description' => 'Retail customers'],
            ['group_code' => 'GRP-004', 'group_name' => 'Government', 'description' => 'Government institutions'],
            ['group_code' => 'GRP-005', 'group_name' => 'Reseller', 'description' => 'Reseller partners'],
        ];
        foreach ($groups as $group) {
            CustomerGroup::create($group);
        }

        // Create Payment Terms
        $terms = [
            ['term_code' => 'COD', 'term_name' => 'Cash On Delivery', 'days' => 0],
            ['term_code' => 'NET15', 'term_name' => 'Net 15 Days', 'days' => 15],
            ['term_code' => 'NET30', 'term_name' => 'Net 30 Days', 'days' => 30],
            ['term_code' => 'NET45', 'term_name' => 'Net 45 Days', 'days' => 45],
            ['term_code' => 'NET60', 'term_name' => 'Net 60 Days', 'days' => 60],
        ];
        foreach ($terms as $term) {
            PaymentTerm::create($term);
        }

        // Create Bank Accounts
        $banks = [
            ['bank_name' => 'Bank BCA', 'account_number' => '1234567890', 'account_name' => 'PT Finance AR'],
            ['bank_name' => 'Bank Mandiri', 'account_number' => '0987654321', 'account_name' => 'PT Finance AR'],
        ];
        foreach ($banks as $bank) {
            BankAccount::create($bank);
        }
    }
}
