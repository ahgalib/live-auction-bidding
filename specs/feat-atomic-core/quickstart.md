# Quickstart: Atomic Bid Core

## Prerequisites
- PHP and Composer dependencies installed
- Application environment configured
- Redis and MySQL accessible

## 1. Prepare environment
```powershell
composer install
Copy-Item .env.example .env -ErrorAction SilentlyContinue
php artisan key:generate
php artisan migrate
```

## 2. Run tests for bidding core
```powershell
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 3. Validate race condition behavior
1. Start two concurrent bid requests for the same auction and same amount.
2. Confirm one request succeeds and one fails with low-bid outcome.
3. Confirm live state shows exactly one winner and consistent current price.

## 4. Validate anti-sniping
1. Set an active auction to less than 30 seconds remaining.
2. Submit a valid higher bid.
3. Confirm end time extends by 60 seconds and update is broadcast.

## 5. Validate audit integrity
1. Place accepted and rejected/outbid bids.
2. Confirm immutable bid events are persisted with actor and timestamp metadata.
3. Confirm admin withdrawal restores prior valid bid and emits corresponding update event.
