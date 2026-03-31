# Auction Engine Constitution

## 1. Architectural Mandates
- State-First: Redis is the authoritative source for live prices and timers.
- Persistence: MySQL is the asynchronous audit log for every transaction.
- API: GraphQL only. REST endpoints are transitional compatibility only.
- Concurrency: Every bid must use a Redis atomic lock (`Cache::lock`).

## 2. Technical Standards
- Time: All timestamps are UTC (ISO 8601).
- Frontend: Vue 3 (Composition API) + Pinia + Apollo.
- WebSockets: Laravel Echo + Pusher direct broadcast for live bid updates.

## 3. Performance
- Target: less than 50ms backend response time for bid hot-path execution.
- Async: Use `dispatchAfterResponse()` for all non-critical MySQL writes.
