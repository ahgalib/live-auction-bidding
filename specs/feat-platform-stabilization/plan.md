# Implementation Plan: Platform Stabilization + Admin Auction Management

## 1. Context
- **Branch:** `feat-platform-stabilization`
- **Goal:** Stabilize active auth/bid flow, remove critical contract mismatches, and add admin auction management in a structured release.

## 2. Technical Context
- **Backend**: Laravel 12, GraphQL controller pattern, Redis + MySQL.
- **Frontend**: Vue 3 + Pinia + router, GraphQL request helper.
- **Auth**: bearer token backed by `oauth_access_tokens`.

## 3. Workstreams
1. **Session Stability**
   - Correct token parsing/storage in active auth store.
   - Ensure protected calls keep valid bearer value.
2. **Auction Room Consistency**
   - Route active bid room to GraphQL-compatible realtime room.
   - Unify room authentication wiring with active auth store.
3. **Admin Management**
   - Add admin GraphQL operations for list/create/update/delete auctions.
   - Add admin auction management UI and route guards.
4. **Contract Alignment**
   - Update GraphQL schema documentation to current runtime fields.
   - Add tests for admin GraphQL authorization and CRUD behavior.

## 4. Risk Controls
- Preserve `AuctionBiddingService` hot-path logic (no behavioral regression in lock/anti-sniping flow).
- Keep REST compatibility routes untouched while frontend migrates to GraphQL-first runtime path.
- Validate with focused backend feature tests after changes.
