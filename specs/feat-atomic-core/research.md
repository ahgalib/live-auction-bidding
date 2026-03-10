# Phase 0 Research: Atomic Bid Core

## Decision 1: Serialize bid writes per auction
- **Decision**: Use a per-auction atomic lock in live state before validating and applying bids.
- **Rationale**: Guarantees deterministic ordering and removes race conditions for equal-amount bids.
- **Alternatives considered**:
  - Optimistic writes only: rejected due to higher collision/retry ambiguity under burst traffic.
  - DB-first row locking: rejected for hot path latency and deadlock risk under high concurrency.

## Decision 2: Treat live state as authoritative for bid acceptance
- **Decision**: Validate bids against live auction state in memory and only persist results asynchronously.
- **Rationale**: Supports sub-50ms bid response target and high-frequency contention windows.
- **Alternatives considered**:
  - Synchronous DB validation: rejected for slower response and scalability bottlenecks.
  - Dual validation (memory + DB on each bid): rejected for added latency and complexity.

## Decision 3: Tie behavior for same-amount concurrent bids
- **Decision**: First accepted bid at the lock boundary wins; later equal bids are rejected as too low.
- **Rationale**: Deterministic and transparent rule that prevents split-winner states.
- **Alternatives considered**:
  - Random tie break: rejected due to fairness disputes and audit complexity.
  - Shared winner state for equal bids: rejected as invalid for auction semantics.

## Decision 4: Anti-sniping extension policy
- **Decision**: If a valid bid is accepted in the final 30 seconds, extend auction by 60 seconds.
- **Rationale**: Prevents last-moment sniping and keeps competition fair.
- **Alternatives considered**:
  - No extension: rejected for fairness issues.
  - Variable extension durations: rejected for reduced predictability.

## Decision 5: Immutable bid event logging
- **Decision**: Append-only bid log records for accepted and outbid events.
- **Rationale**: Enables full audit trail, dispute resolution, and fraud analysis.
- **Alternatives considered**:
  - Mutable “current bid only” records: rejected; loses historical traceability.
  - Delayed batch summary logs: rejected due to insufficient event granularity.

## Decision 6: Real-time update signaling
- **Decision**: Broadcast bid update events immediately after live state mutation.
- **Rationale**: Keeps all participants synchronized with minimal staleness.
- **Alternatives considered**:
  - Polling-only clients: rejected due to stale UX and wasted traffic.
  - Timed batch notifications: rejected for high-latency reactions.

## Decision 7: Shared hosting operational profile
- **Decision**: Keep persistence asynchronous after response and avoid persistent custom worker assumptions.
- **Rationale**: Matches deployment constraints while preserving hot-path responsiveness.
- **Alternatives considered**:
  - Fully synchronous writes: rejected for response latency.
  - Dedicated always-on ingestion workers only: not assumed for baseline environment.
