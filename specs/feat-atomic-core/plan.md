# Implementation Plan: Atomic Bid Core

## 1. Context
- **Branch:** `feat-atomic-core`
- **Goal:** Deliver a high-concurrency auction bidding core that guarantees consistent winner selection, real-time updates, anti-sniping, and immutable audit history.

## 2. Technical Context
**Language/Version**: PHP 8.2  
**Primary Dependencies**: Laravel 12, Redis, MySQL  
**Storage**: Redis for live auction state; MySQL for immutable bid logs and settlement records  
**Testing**: PHPUnit (Laravel test runner), feature and integration tests  
**Target Platform**: Linux-based web server environment  
**Project Type**: Web service backend  
**Performance Goals**: Bid response under 50ms and broadcast propagation under 200ms at expected load  
**Constraints**: Strong correctness under concurrent equal-amount bids; no mutable bid history; low operational overhead on shared hosting  
**Scale/Scope**: Sustain at least 50 bid attempts/second across active auctions and live watcher fanout

## 3. Constitution Check

Pre-Phase 0 gate review against `.specify/memory/constitution.md`:

- **Architecture gate:** PASS. Plan follows speed-path separation: fast live state, signaling, and durable audit.
- **Concurrency gate:** PASS. Per-auction atomic lock and deterministic validation are required.
- **Integrity gate:** PASS. Every successful live state update must be durably logged asynchronously.
- **Realtime gate:** PASS. Accepted bids trigger broadcast updates to active watchers.
- **Performance gate:** PASS. Plan keeps hot-path decisions in memory and avoids slow-path reads in bid decision path.

Post-Phase 1 gate review:

- **Design outputs maintain all gates:** PASS.

## 4. Data Strategy
- **Redis Keys:**
  - `auction:{id}:current_price`
  - `auction:{id}:current_winner_id`
  - `auction:{id}:end_time`
  - `auction:{id}:lock`
  - `auction:{id}:watcher_count`
- **MySQL Tables:**
  - `users`
  - `auctions`
  - `bid_logs`
  - `settlements` (or equivalent settlement table)
- **Public Interfaces:**
  - Bid placement entry point
  - Auction state query
  - Server time query
  - Bid update subscription stream
  - Admin bid withdrawal action

## 5. The Atomic Logic (Flow)
1. Acquire short-lived per-auction lock for incoming bid.
2. Read current live state and validate auction status, amount rules, and tie behavior.
3. On valid bid, update live amount, winner, and timer extension if anti-sniping window applies.
4. Publish live update event to connected watchers.
5. Dispatch asynchronous immutable log persistence.
6. Release lock and return deterministic success or rejection result.

## 6. TDD Plan
- **Test A:** Accept valid higher bid and reject low/equal bid with deterministic outcomes.
- **Test B:** Concurrency race test for simultaneous equal-amount bids to ensure single winner.
- **Test C:** Anti-sniping extension in final 30 seconds with broadcasted new end time.
- **Test D:** Audit persistence verification for accepted and outbid events.
- **Test E:** Admin withdrawal restores prior valid state with audit trace.

## 7. Project Structure

```text
app/
  Http/
  Models/
  Services/
  Jobs/
  Events/

database/
  migrations/

routes/
  web.php
  api.php

tests/
  Feature/
  Integration/
```

Structure is single backend Laravel service; bidding core is implemented in service + job + event layers with feature/integration tests.
