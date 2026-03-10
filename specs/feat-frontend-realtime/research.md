# Research: Realtime Auction Frontend

## Decision: Server offset clock model
- **Decision**: Maintain a global server-client offset and derive reactive `now` from local time + offset.
- **Rationale**: Keeps countdown accurate despite device clock drift.
- **Alternatives considered**: Local clock only (rejected), per-component server polling only (rejected).

## Decision: Buffered bid feed updates
- **Decision**: Buffer bid feed events and flush every 200ms.
- **Rationale**: Prevents frequent re-renders under burst bidding.
- **Alternatives considered**: Immediate render for each event (rejected due to jank risk).

## Decision: Optimistic bid UX with rollback
- **Decision**: Insert local pending bid entry immediately; on error remove/mark failed and show toast.
- **Rationale**: Keeps interaction instant while preserving correctness.
- **Alternatives considered**: Wait-only-for-server response (rejected due to sluggish feel).

## Decision: Visibility API hard re-sync
- **Decision**: Trigger hard state refresh whenever tab becomes visible.
- **Rationale**: Browser throttling can skip events; hard sync recovers truth.
- **Alternatives considered**: Passive recovery only via socket reconnect (rejected).
