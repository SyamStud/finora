<?php

namespace Database\Seeders;

use App\AccountType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Pocket;
use App\Models\PocketAllocation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    /** Run the database seeds. */
    public function run(): void
    {
        /** @var User $user */
        $user = User::where('email', 'demo@finora.test')->firstOrFail();

        $bca = Account::factory()->for($user)->create([
            'name' => 'BCA',
            'type' => AccountType::Bank,
            'initial_balance' => 5_000_000,
            'color' => '#1b4b4a',
        ]);
        $cash = Account::factory()->for($user)->create([
            'name' => 'Cash',
            'type' => AccountType::Cash,
            'initial_balance' => 250_000,
            'color' => '#e8694f',
        ]);
        $gopay = Account::factory()->for($user)->create([
            'name' => 'GoPay',
            'type' => AccountType::EWallet,
            'initial_balance' => 500_000,
            'color' => '#2e6d66',
        ]);

        // Pocket selalu dibuat di dalam rekening, bukan akun mandiri.
        $jajan = Pocket::factory()->for($user)->for($bca)->create([
            'name' => 'Jajan', 'color' => '#f48066',
        ]);
        $kebutuhan = Pocket::factory()->for($user)->for($bca)->create([
            'name' => 'Kebutuhan', 'color' => '#8fc2b8',
        ]);

        $food = Category::factory()->for($user)->expense()->create([
            'name' => 'Food & Drink', 'icon' => 'utensils', 'color' => '#e8694f',
        ]);
        $transport = Category::factory()->for($user)->expense()->create([
            'name' => 'Transportation', 'icon' => 'car', 'color' => '#f48066',
        ]);
        $salary = Category::factory()->for($user)->income()->create([
            'name' => 'Salary', 'icon' => 'briefcase', 'color' => '#2e6d66',
        ]);

        // Alokasi dari saldo dasar ke pocket: total BCA tidak berubah.
        PocketAllocation::factory()->for($user)->for($bca)->create([
            'source_pocket_id' => null,
            'destination_pocket_id' => $jajan->id,
            'amount' => 500_000,
            'note' => 'Alokasi uang jajan',
            'allocated_at' => now()->startOfMonth(),
        ]);
        PocketAllocation::factory()->for($user)->for($bca)->create([
            'source_pocket_id' => null,
            'destination_pocket_id' => $kebutuhan->id,
            'amount' => 1_000_000,
            'note' => 'Alokasi kebutuhan bulanan',
            'allocated_at' => now()->startOfMonth(),
        ]);

        Transaction::factory()->for($user)->for($bca)->for($salary)->income()->create([
            'description' => 'Gaji bulanan', 'amount' => 8_000_000,
            'transaction_date' => now()->startOfMonth(),
        ]);
        Transaction::factory()->for($user)->for($bca)->for($jajan)->for($food)->expense()->create([
            'description' => 'Beli kopi', 'amount' => 25_000,
            'transaction_date' => now()->subDays(2),
        ]);
        Transaction::factory()->for($user)->for($cash)->for($transport)->expense()->create([
            'description' => 'Bensin', 'amount' => 50_000,
            'transaction_date' => now()->subDay(),
        ]);

        Budget::factory()->for($user)->for($food)->create([
            'amount' => 1_000_000, 'start_date' => now()->startOfMonth(),
        ]);

        Asset::factory()->for($user)->create([
            'name' => 'Money Market Fund',
            'type' => 'mutual_fund',
            'units' => 1_250,
            'average_buy_price' => 1_200,
            'current_price' => 1_250,
            'invested_amount' => 1_500_000,
            'current_value' => 1_562_500,
        ]);
    }
}
