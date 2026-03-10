# Implementation Plan: Realtime Auction Frontend

## 1. Context
- **Branch:** `feat-frontend-realtime`
- **Goal:** Build a Vue 3 real-time auction UI with high-precision countdown sync, optimistic bidding, and resilient live updates.

## 2. Technical Context
**Language/Version**: JavaScript (ES modules)  
**Primary Dependencies**: Vue 3, Pinia, Apollo Client, Laravel Echo, Pusher JS, date-fns, Tailwind CSS  
**Storage**: Browser state (Pinia + local storage token)  
**Testing**: Frontend unit/component tests and manual integration validation  
**Target Platform**: Modern desktop/mobile browsers  
**Project Type**: Web frontend SPA mounted through Laravel Blade  
**Performance Goals**: Smooth UI under burst event flow, bid action feedback under 100ms, countdown precision under 1s drift  
**Constraints**: Shared hosting constraints, intermittent socket disconnects, user device clock drift  
**Scale/Scope**: Single auction room view with live updates and authenticated bid actions

## 3. Constitution Check
- Realtime update path and auction correctness are preserved from backend architecture.
- Frontend decisions do not violate immutable audit constraints.
- No blocking gate failures identified.

## 4. Data Strategy
- **State domains**:
  - Auction state and buffered feed
  - Server time offset state
  - Session/auth token state
- **Data sources**:
  - REST bootstrapping (`/api/auctions/{id}`, `/api/server-time`)
  - Bid action endpoint (`/api/auctions/{id}/bid`)
  - Optional GraphQL/Apollo layer for future migration
  - Echo/Pusher private channel updates

## 5. Atomic UI Flow
1. Bootstrap auction state + server offset.
2. Start countdown from offset-adjusted time.
3. Subscribe to real-time events and merge updates.
4. Apply optimistic bid updates and rollback on failure.
5. Re-sync on visibility changes and reconnection.

## 6. TDD Plan
- Validate server-time offset calculation.
- Validate optimistic bid push/rollback behavior.
- Validate buffer flush behavior for burst updates.
- Validate disconnection overlay and re-sync behavior.

## 7. Project Structure

```text
resources/js/
  api/
  components/
    auction/
    shared/
  composables/
  stores/
  views/
resources/views/
  auction-room.blade.php
```
