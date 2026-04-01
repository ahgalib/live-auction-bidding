# Velocity Auction System Design Review

## Purpose

This document explains the current auction platform in plain language based on the actual codebase and the available spec files. It covers:

- overall system design
- tools and technologies used
- why each tool is used
- where each tool is used in the code
- benefits of the current approach
- how time handling works
- what happens if many users bid at once
- important edge cases
- design gaps between the original specs and the current implementation

## 1. High-level system overview

This project is a live auction platform split into two main applications:

- `live-auction-bidding`: Laravel backend
- `auction-front-end`: Vue 3 frontend

At a high level, the system works like this:

1. The frontend loads auction data through GraphQL.
2. A user logs in and gets a bearer token.
3. The user submits a bid using a GraphQL mutation.
4. The backend serializes competing bids with a Redis-backed lock.
5. If the bid is valid, the backend updates auction state and writes an audit log.
6. The backend broadcasts a realtime event with Pusher/Laravel Echo.
7. Other connected clients receive the update and refresh visible auction state.

So the architecture is basically:

- Vue frontend for UI and interaction
- GraphQL for data contract
- Laravel for business logic
- MySQL for durable storage
- Redis for fast live-state coordination and locking
- Pusher/WebSockets for realtime delivery

## 2. Main technologies and why they are used

### Vue 3

Used in `auction-front-end/src`.

Why used:

- good fit for reactive realtime UI
- Composition API makes auction logic reusable through composables
- component-based structure helps separate timer, bid pad, feed, and status display

Where it appears:

- views such as `src/views/AuctionRoom.vue`
- components such as `src/components/auction/AuctionCountdown.vue`
- composables such as `src/composables/useAuctionSubscription.js`

Benefit:

- keeps UI state reactive
- makes realtime updates easier to reflect without full page refresh

### Pinia

Used in frontend stores such as `src/stores/auctionStore.js` and `src/stores/auth.js`.

Why used:

- gives a central source of truth for auction state and auth state
- prevents many components from owning conflicting copies of the same data

Benefit:

- easier synchronization between price, bid feed, connection state, and user session

### Laravel

Used in `live-auction-bidding/app`, `routes`, and `config`.

Why used:

- good backend framework for routing, validation, database access, broadcasting, and auth-related work
- works well with Redis, MySQL, and event broadcasting

Benefit:

- business rules stay on the server
- validation and authorization are centralized

### GraphQL

Used as the main frontend-backend data interface through `POST /graphql`.

Where it appears:

- backend controller: `app/Http/Controllers/GraphqlController.php`
- schema doc: `project-doc/graphql-schema.graphql`
- frontend client wrapper: `auction-front-end/src/api/graphql.js`
- frontend queries/mutations in `auction-front-end/src/api/queries` and `auction-front-end/src/api/mutations`

Why GraphQL is used:

- the frontend can ask for exactly the shape it needs
- one endpoint can support multiple operations
- auction, auth, and admin data all fit into one contract style
- better than many separate REST endpoints when UI screens need nested structured data

Benefit:

- cleaner frontend contract
- easier evolution of UI responses
- less endpoint sprawl

Why it makes sense here:

- auction room needs a bundle of data at once: current price, winner, end time, participant count, bid feed
- GraphQL lets the room hydrate from one structured response

Important note:

- REST endpoints still exist in `routes/api.php`, but the active frontend has mostly moved to GraphQL-first behavior

### MySQL

Used as the permanent database.

Where it appears:

- config: `config/database.php`
- models: `app/Models/Auction.php`, `app/Models/BidLog.php`, `app/Models/User.php`
- migrations in `database/migrations`

Why used:

- durable source of truth for users, auctions, and bid logs
- relational structure is a good fit for auction ownership, winners, audit trails, and admin management

Benefit:

- persistent storage
- auditability
- easier reporting and recovery compared with keeping everything in memory

### Redis

Used as the fast coordination layer.

Where it appears:

- cache config defaults to Redis in `config/cache.php`
- Redis connections configured in `config/database.php`
- lock usage in `app/Services/AuctionBiddingService.php`

Why Redis is used:

- locking needs to be fast
- live state keys are lightweight and frequently accessed
- Redis is much better for short-lived coordination than MySQL row contention alone

Main Redis responsibilities in this codebase:

- protect bid critical section using `Cache::lock(...)`
- cache live price, winner id, and end time
- store request idempotency results temporarily

Benefit:

- reduces race conditions
- makes hot-path bid handling faster
- helps prevent duplicate processing

### Pusher + Laravel Echo

Used for realtime event delivery.

Where it appears:

- backend event: `app/Events/BidUpdated.php`
- broadcasting config: `config/broadcasting.php`
- frontend bootstrapping: `auction-front-end/src/bootstrap.js`
- frontend listener: `auction-front-end/src/composables/useAuctionSubscription.js`

Why used:

- polling alone would feel slow in a live auction
- users need to see bid changes almost instantly
- WebSocket-style delivery is the right tool for live bidding rooms

Benefit:

- near-instant updates
- less wasteful than repeatedly reloading full auction state every second

Important detail:

- this implementation uses a normal channel, not a presence channel
- that means the system is not truly tracking all live watchers through Pusher presence right now

### Notifications

There are two meanings of notification in this project:

1. frontend user feedback notifications
2. realtime bid update broadcasts

Frontend notifications:

- shown as toast messages in `auction-front-end/src/components/shared/ToastMessage.vue`
- used for events like successful bid, outbid, login required, or another user placing a bid

Realtime notifications:

- sent through the `BidUpdated` event
- consumed in the frontend subscription composable

Why needed:

- live auction users need immediate feedback
- silent failures create distrust

Benefit:

- improves clarity and user confidence
- keeps the room feeling alive

## 3. Core backend design

The most important backend class is `app/Services/AuctionBiddingService.php`.

This class handles:

- bid validation
- concurrency control
- live state hydration
- audit logging
- idempotency
- admin bid withdrawal
- realtime event broadcast

### Bid flow

When a user places a bid:

1. the backend acquires a lock for that auction
2. it checks if the same request was already processed using `requestId`
3. it refreshes the auction and current live state
4. it rejects the bid if the auction is closed
5. it rejects the bid if the amount is below the minimum allowed
6. if accepted, it updates live state and auction DB record
7. it inserts an audit record into `bid_logs`
8. it broadcasts a `BidUpdated` event

This is the most important safety path in the whole system.

### Why the lock matters

Without a lock, two users could both read the same current price and both think their bid is valid.

Example:

- current price is 500
- user A bids 550
- user B bids 550 at almost the same moment

With the lock:

- only one request enters the critical section first
- the winner updates the price to 550
- the next request sees 550 already exists
- the second request gets `LOW_BID`

This is how the system preserves one consistent winner.

### Idempotency

The backend supports `requestId` for bids.

Why this matters:

- mobile clients retry
- double-clicks happen
- network instability can resend the same mutation

Benefit:

- the same bid request is not accepted twice
- duplicate accepted audit rows are avoided

### Audit log design

The `bid_logs` table stores accepted, outbid, and withdrawn events.

Why this matters:

- auction systems need history
- support teams need traceability
- fraud review needs evidence
- business logic should not overwrite history

Benefit:

- immutable-style event trail
- easier debugging
- better trust and accountability

## 4. Core frontend design

The frontend is built around a few key ideas:

- one central auction store
- one realtime subscription composable
- one room page that composes specialized components

### Auction store

File:

- `auction-front-end/src/stores/auctionStore.js`

This store owns:

- current price
- title and metadata
- participant count
- bid feed
- connection state
- whether bidding is active

Why this is good:

- all room components read the same source of truth
- easier to resync after network issues

### Realtime subscription composable

File:

- `auction-front-end/src/composables/useAuctionSubscription.js`

This composable:

- subscribes to the auction channel
- listens for `.BidUpdated`
- applies incoming price changes
- buffers bid feed items for 200ms before flushing
- updates connection state
- falls back to polling if WebSocket is unavailable
- triggers hard resync when tab becomes visible again

Why buffering is used:

- if many bids arrive together, pushing every item directly can cause many re-renders
- buffering makes burst traffic less expensive for the UI

Benefit:

- smoother UI under burst load

### Countdown component

File:

- `auction-front-end/src/components/auction/AuctionCountdown.vue`

This component:

- uses `requestAnimationFrame`
- updates visible time every 250ms
- calculates `remaining = target - Date.now()`
- prevents countdown from jumping upward for the same target

Benefit:

- efficient rendering
- avoids some visible timer jitter

## 5. Why GraphQL was chosen here

GraphQL is a strong fit for this system because the auction room is a data-heavy screen.

The room wants many related fields together:

- auction metadata
- current price
- current winner
- end time
- participant count
- recent bid feed

If this were fully REST-driven, the frontend would usually need:

- one endpoint for auction
- one for bids
- one for user profile
- one for admin info
- maybe one for time sync

GraphQL simplifies that into named operations with predictable response shapes.

### Main GraphQL operations in the project

- `Auctions`
- `AuctionState`
- `Register`
- `Login`
- `OAuthToken`
- `Me`
- `Logout`
- `PlaceBid`
- `WithdrawBid`
- admin auction mutations

### Benefits of GraphQL in this project

- fewer custom endpoints
- easier frontend hydration
- better fit for admin dashboard and auction room
- easy to evolve fields without redesigning many routes

### Tradeoff

The backend is not using a full GraphQL schema engine such as Lighthouse at runtime. Instead, it manually routes named operations inside one controller. That is simpler in the short term, but it also means:

- less schema enforcement at runtime
- more manual controller logic
- more risk of docs drifting from implementation

## 6. Why Redis is important here

Redis is not just a cache in this system. It is part of the concurrency design.

### What Redis is doing

- serializing competing bids with locks
- caching hot live auction values
- storing short-lived idempotency results

### Why not rely only on MySQL

MySQL is durable, but it is not the best place for every hot, high-frequency coordination task.

If all contention lived directly in MySQL:

- latency would be higher
- lock contention would be heavier
- scaling concurrent bids would be harder

Redis gives the system a fast lane for the hot path, while MySQL remains the durable record.

## 7. Why Pusher/Echo is important here

Live auctions depend on shared visibility.

If one bidder sees 700 and another still sees 650, trust breaks quickly.

Pusher/Echo helps solve that by pushing `BidUpdated` events immediately after accepted or withdrawn bids.

### Where it helps

- current price updates
- participant feedback
- bid feed updates
- connection status awareness

### Current limitation

The code currently uses `window.Echo.channel(...)`, which is a standard channel. The design docs talk about presence/live watchers, but this implementation does not really maintain true watcher presence through Pusher presence channels. The frontend currently sets:

- `watcherCount = participantCount`

So the displayed live bidder count is closer to "distinct accepted bidders seen in audit logs" than "all active watchers currently connected."

That is an important distinction.

## 8. Time design and the “global time” question

This is the biggest design change between the original specs and the current code.

### Original design intent

The specs and docs originally wanted:

- server time endpoint/query
- client offset calculation
- countdown based on `server time - client offset`

That approach is usually better for fairness because client clocks are often wrong.

### Current implementation

The current code has intentionally removed active server-time sync behavior.

Evidence:

- `specs/feat-remove-server-time-sync/spec.md` says server-time usage should be removed
- `auction-front-end/src/composables/useServerTime.js` now sets offset to `0`
- `auction-front-end/src/components/auction/AuctionCountdown.vue` uses `Date.now()` directly
- countdown logic is now browser-clock-based

### Why UTC/global time still matters

Even without live offset sync, the system still correctly stores and returns auction times in UTC ISO 8601 format.

That is important because:

- users may be in different countries
- backend and frontend may run in different time zones
- logs need consistent timestamps
- sorting and comparison become predictable

UTC is the shared global clock format. It prevents timezone confusion.

### Why server time sync was a good original idea

If a user’s device clock is wrong by 20 seconds:

- their countdown will also be wrong if the app trusts local `Date.now()`

This can create:

- confusion at the auction end
- complaints about fairness
- mismatch between visible client timer and actual backend acceptance

### Current practical outcome

Right now:

- the backend decides whether the auction is closed
- the frontend displays a best-effort local countdown

That means the backend remains authoritative, which is good, but the visual timer may still drift on incorrect client clocks.

### Recommendation

If fairness is critical, bring back server-time offset sync. Keep UTC everywhere and make the frontend countdown use:

- `remaining = server_end_time - (Date.now() + offset)`

If simplicity is more important than precision, the current approach is acceptable, but it should be clearly documented as a tradeoff.

## 9. What happens if 1000 people join the same auction

This question has two parts:

- 1000 people watching
- many people bidding at nearly the same time

### 1000 watchers

What will work:

- GraphQL auction hydration can still serve room state
- Pusher can fan out bid events to many clients
- frontend listener is lightweight enough for normal event handling

What may become weak:

- current watcher count is not true presence tracking
- every accepted event recalculates participant count from the database
- frequent `participantCount()` calls may become expensive at larger scale

### 1000 people trying to bid

The locking strategy protects correctness, but it also serializes throughput per auction.

That means:

- only one bid update is processed at a time for a given auction
- this preserves consistency
- but it also means one very hot auction becomes a bottleneck by design

This is not necessarily wrong. In auctions, correctness is more important than parallel acceptance of conflicting bids.

### Likely behavior under heavy contention

If 1000 users submit around the same time:

1. requests queue behind the auction lock
2. the first valid request updates the state
3. later requests re-read and many will become `LOW_BID`
4. the frontend receives many updates or failures quickly
5. the winning price remains consistent

### Good part of current design

- consistency is prioritized
- only one accepted winner progression is possible

### Scaling risks

- lock wait time can increase
- participant count DB query may become hot
- broadcasting every event synchronously may increase response latency
- reloading canonical state too often from the frontend can increase backend load

### If you truly expect 1000 concurrent bidders on one auction

Recommended next steps:

- use true presence channels for watcher tracking
- store participant count in Redis and sync to DB asynchronously
- separate watcher count from distinct accepted-bidder count
- consider queue-backed broadcasting if sync broadcast hurts response time
- consider per-auction rate limiting and backpressure
- load test the lock path specifically

## 10. Edge cases and how the code handles them

### Race condition: two users bid same amount at same time

Handled by Redis-backed lock.

Result:

- one succeeds
- one gets `LOW_BID`

### Duplicate request/retry

Handled by `requestId` idempotency.

Result:

- the same request can return the same cached result instead of writing duplicate accepted rows

### Out-of-order realtime events

Handled partly in the frontend subscription.

The code prevents price from moving backward on normal accepted bid events by using:

- `Math.max(currentPrice, incomingAmount)`

Withdrawn bids are allowed to move the price backward intentionally.

### Realtime disconnect

Handled in frontend by:

- connection state changes
- connection overlay
- polling fallback when Echo is unavailable

### Tab inactive / backgrounded

Handled with `visibilitychange`.

When the tab becomes visible again:

- frontend triggers hard resync

This is good because browsers may pause timers and background activity.

### Reloading the page during auction

Handled by returning recent bids from `AuctionState`.

This lets the room restore bid feed state after reload.

### Auction reaches end time

Backend is authoritative:

- if `end_time` is past, bid is rejected

Frontend behavior:

- countdown reaches zero
- room re-fetches
- closed state shows winner/final price

## 11. Current design gaps and spec drift

This codebase has useful architecture, but some design documents do not fully match the current implementation.

### Gap 1: Server time sync

Original docs/specs:

- wanted active server-time synchronization

Current code:

- removed active server-time endpoint usage
- uses browser clock directly

Impact:

- simpler frontend
- weaker countdown accuracy on skewed devices

### Gap 2: Anti-sniping

Several specs and tests describe anti-sniping timer extension in the last 30 seconds.

Current `AuctionBiddingService` behavior:

- does not extend the timer on bid accept
- explicitly keeps auction time DB-canonical and admin-controlled

Impact:

- actual runtime behavior appears different from the original architecture docs
- if anti-sniping is still a business requirement, current implementation needs re-checking

### Gap 3: Presence/watcher count

Original docs:

- imply true live watcher presence

Current code:

- shows watcher count based on participant count or last known count
- does not use Pusher presence channels for real watcher membership

Impact:

- UI label may overstate what is truly being measured

### Gap 4: GraphQL runtime style

Docs imply GraphQL-first architecture.

Current implementation:

- uses GraphQL-shaped operations, but manual operation dispatch in a controller

Impact:

- works fine
- but schema and controller logic can drift more easily

## 12. Strengths of the current system

- clear separation between frontend and backend
- strong bid correctness through locking
- GraphQL contracts make frontend integration cleaner
- audit trail is built in
- realtime updates exist and are integrated well enough for a working auction room
- visibility-based resync and buffered feed updates show good practical thinking
- admin auction management is already supported

## 13. Weaknesses or areas to improve

- time-sync story is inconsistent across specs and implementation
- anti-sniping appears documented more strongly than it is currently implemented
- watcher count is not true presence
- participant count is computed from DB repeatedly and may become expensive
- manual GraphQL dispatch is workable, but not ideal long term
- there is no evidence here of a full-scale load-tested hot-auction strategy

## 14. Recommended architecture direction

If this system is meant for real production-grade high-traffic live auctions, the best direction would be:

1. Keep GraphQL for room and admin contracts.
2. Keep Redis locking for per-auction correctness.
3. Keep MySQL as durable source of truth and audit ledger.
4. Keep Pusher/Echo for realtime fan-out.
5. Reintroduce server-time offset sync if fairness matters.
6. Decide clearly whether anti-sniping is a business rule or not.
7. Implement true presence channels if live watcher count is important.
8. Move expensive hot-path aggregates from repeated DB reads toward Redis-backed counters where safe.
9. Load test a single hot auction with many simultaneous bid attempts.

## 15. Simple explanation for non-technical stakeholders

This system uses:

- MySQL to permanently save auctions, users, and bid history
- Redis to make bidding fast and safe when many people bid together
- GraphQL so the frontend can ask for exactly the auction data it needs
- Pusher/WebSockets so everyone sees new bids quickly without refreshing
- Vue and Pinia to keep the auction screen responsive and consistent

The backend is the final authority for whether a bid wins or loses. Even if many users click at the same moment, the locking system ensures only one consistent auction state is accepted at a time.

The biggest current design question is time fairness. The original design wanted server-synced countdowns, but the current frontend now uses the browser clock. That is simpler, but less accurate when a user device clock is wrong.

## 16. Final conclusion

The system is built on a sensible modern architecture for a live auction platform:

- GraphQL for contract-driven data access
- Redis for concurrency and fast live state
- MySQL for durable records
- Pusher for realtime updates
- Vue/Pinia for interactive room behavior

The strongest part of the design is bid correctness. The weakest part right now is architectural consistency around time synchronization, anti-sniping, and live watcher tracking.

So the short version is:

- the core auction engine direction is good
- the realtime UX direction is good
- the documentation and runtime behavior need to be aligned before calling the design fully production-ready
