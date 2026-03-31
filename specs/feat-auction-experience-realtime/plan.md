# Implementation Plan: Auction Discovery + Admin Time Control + Realtime Notifications

## 1. Context
- **Branch:** `feat-auction-experience-realtime`
- **Goal:** Improve real user flow from login to auction selection and add practical realtime/admin controls required for operations.

## 2. Technical Context
- **Backend:** Laravel 12, GraphQL controller-driven API, MySQL, broadcasting via Pusher driver.
- **Frontend:** Vue 3 + Pinia + Apollo client + Laravel Echo.
- **Realtime:** Broadcast `BidUpdated` event with bidder context and room-consumable payload.

## 3. Workstreams
1. **Data Model Enrichment**
   - Add auction purpose/description field.
   - Expose participant count in GraphQL read models.
2. **GraphQL API Expansion**
   - Add `Auctions` list query for active auctions.
   - Add `AdjustAuctionTime` admin mutation.
   - Extend auction/admin DTO fields to include description and participant count.
3. **Broadcasting Implementation**
   - Convert `BidUpdated` to broadcast event with Pusher-compatible channel + payload.
   - Add missing broadcasting configuration artifacts.
4. **Frontend Experience**
   - Add authenticated auction list page as default post-login destination.
   - Add admin UI controls for +/- minute end-time adjustment.
   - Show auction product/purpose and participant count in room/list.
   - Show bidder identity notifications on incoming bid events.

## 4. Validation
- Backend feature tests for auctions list + time adjustment authorization.
- Existing GraphQL bid tests remain green.
- Frontend production build compiles after route/view/store changes.
