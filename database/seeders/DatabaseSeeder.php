<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\User;
use Carbon\CarbonImmutable;
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
        $admin = User::query()->updateOrCreate(
            ['email' => 'galib@dev.com'],
            [
                'name' => 'Asadulla Galib',
                'password' => 'password123',
                'is_admin' => true,
                'wallet_balance' => 5000.00,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'rayhan@test.com'],
            [
                'name' => 'Rayhan Ahmed',
                'password' => 'password123',
                'is_admin' => false,
                'wallet_balance' => 1200.00,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'naimur@test.com'],
            [
                'name' => 'Naimur Rahman',
                'password' => 'password123',
                'is_admin' => false,
                'wallet_balance' => 2500.00,
            ],
        );

        $seedAuctions = [
            [
                'title' => 'iPhone 15 Pro Max (Deep Purple)',
                'starting_price' => 900.00,
                'min_increment' => 10.00,
                'current_price' => 900.00,
                'end_time' => CarbonImmutable::parse('2026-04-01 18:00:00', 'UTC'),
                'category' => 'Electronics',
            ],
            [
                'title' => 'MacBook Air M2 (8GB/256GB)',
                'starting_price' => 750.00,
                'min_increment' => 15.00,
                'current_price' => 750.00,
                'end_time' => CarbonImmutable::parse('2026-03-31 22:30:00', 'UTC'),
                'category' => 'Computing',
            ],
            [
                'title' => 'Sony WH-1000XM5 Headphones',
                'starting_price' => 250.00,
                'min_increment' => 5.00,
                'current_price' => 250.00,
                'end_time' => CarbonImmutable::parse('2026-03-31 23:15:00', 'UTC'),
                'category' => 'Audio',
            ],
        ];

        foreach ($seedAuctions as $auctionData) {
            Auction::query()->updateOrCreate(
                ['title' => $auctionData['title']],
                [
                    'seller_id' => $admin->id,
                    'starting_price' => $auctionData['starting_price'],
                    'min_increment' => $auctionData['min_increment'],
                    'current_price' => $auctionData['current_price'],
                    'current_winner_id' => null,
                    'end_time' => $auctionData['end_time'],
                    'category' => $auctionData['category'],
                    'status' => 'active',
                ],
            );
        }
    }
}
