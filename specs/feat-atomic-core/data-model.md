# Data Model: Atomic Bid Core

## Entity: User
- **Fields**:
  - `id` (unique identifier)
  - `wallet_balance`
  - `verification_status`
  - `role` (bidder, seller, admin)
- **Relationships**:
  - One user can place many bid events.
  - One user can own many auctions (seller).

## Entity: Auction
- **Fields**:
  - `id`
  - `seller_id`
  - `current_price`
  - `current_winner_id`
  - `end_time`
  - `status` (draft, active, pending_payment, closed)
- **Relationships**:
  - One auction has many bid events.
  - One auction resolves to zero or one settlement record.
- **Validation rules**:
  - Bids accepted only when status is `active`.
  - Incoming bid amount must exceed current live price.
- **State transitions**:
  - `draft -> active -> pending_payment -> closed`
  - `active` end time may extend under anti-sniping rule.

## Entity: Bid Event (Audit Log)
- **Fields**:
  - `id`
  - `auction_id`
  - `user_id`
  - `amount`
  - `event_type` (accepted, outbid, rejected, withdrawn)
  - `timestamp`
  - `ip_address`
  - `request_id` (idempotency/audit correlation)
- **Relationships**:
  - Many bid events belong to one auction and one user.
- **Validation rules**:
  - Append-only; updates/deletes are not allowed.
  - Event timestamp must be server-generated.

## Entity: Live Auction State
- **Fields**:
  - `auction_id`
  - `current_price`
  - `current_winner_id`
  - `end_time`
  - `watcher_count`
  - `last_update_at`
- **Relationships**:
  - Mirrors active auction status for fast decisioning.
- **Validation rules**:
  - Must remain consistent with accepted bid sequence.
  - Closed auction keys expire within 24 hours.

## Entity: Settlement Record
- **Fields**:
  - `id`
  - `auction_id`
  - `winner_id`
  - `final_price`
  - `invoice_reference`
  - `status`
  - `created_at`
- **Relationships**:
  - One settlement references one closed auction.
- **Validation rules**:
  - Must be created atomically with final financial movement.
