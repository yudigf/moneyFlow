<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default user
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Default wallets
        $wallets = [
            ['name' => 'BCA',        'type' => 'bank',     'balance' => 5000000],
            ['name' => 'BNI',        'type' => 'bank',     'balance' => 3000000],
            ['name' => 'BRI',        'type' => 'bank',     'balance' => 1500000],
            ['name' => 'ShopeePay',  'type' => 'e-wallet', 'balance' => 500000],
            ['name' => 'DANA',       'type' => 'e-wallet', 'balance' => 300000],
            ['name' => 'Uang Tunai', 'type' => 'cash',     'balance' => 500000],
        ];

        foreach ($wallets as $wallet) {
            Wallet::firstOrCreate(
                ['name' => $wallet['name']],
                ['type' => $wallet['type'], 'balance' => $wallet['balance']]
            );
        }

        // Default categories
        $categories = [
            ['name' => 'Makan & Minum',        'type' => 'expense'],
            ['name' => 'Transportasi',          'type' => 'expense'],
            ['name' => 'Tagihan & Langganan',   'type' => 'expense'],
            ['name' => 'Investasi',             'type' => 'expense'],
            ['name' => 'Gaji Bulanan',          'type' => 'income'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['type' => $category['type']]
            );
        }
    }
}
