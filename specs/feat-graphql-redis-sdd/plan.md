# Implementation Plan: GraphQL + Redis Backend Alignment

## 1. Context
- **Branch:** `feat-graphql-redis-sdd`
- **Goal:** Align backend transport and runtime defaults to GraphQL-first and Redis-first, while preserving current bidding core behavior.

## 2. Technical Context
**Language/Version**: PHP 8.2  
**Primary Dependencies**: Laravel 12, Redis, MySQL  
**Storage**: Redis for cache/live-state hot path, MySQL for durable records  
**Testing**: PHPUnit feature tests  
**Target Platform**: Linux-based web server environment  
**Project Type**: Web service backend  
**Performance Goals**: Keep existing bid-path latency targets while introducing GraphQL endpoint  
**Constraints**: Maintain compatibility with existing auth/token model

## 3. Constitution Check

- **GraphQL transport**: PASS. `/graphql` endpoint is introduced as primary API surface.
- **Redis-first runtime**: PASS. cache/session/queue defaults are set to Redis in env/config.
- **Atomic correctness**: PASS. GraphQL mutation delegates to existing `AuctionBiddingService`.
- **Operational safety**: PASS. Existing REST remains as compatibility-only while migration completes.

## 4. Work Breakdown
1. Add GraphQL controller and `/graphql` route.
2. Map key operations (`AuctionState`, `ServerTime`, `PlaceBid`, `WithdrawBid`, auth operations).
3. Set Redis runtime defaults in config and env template.
4. Update API docs to GraphQL-first.
5. Add feature tests for GraphQL endpoint behavior.
