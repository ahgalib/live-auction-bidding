# Data Model: Realtime Auction Frontend

## Entity: AuctionViewState
- `auctionId`
- `currentPrice`
- `endTime`
- `watcherCount`
- `isActive`
- `connectionState`

## Entity: BidUIEvent
- `id`
- `amount`
- `userId`
- `createdAt`
- `loading`
- `status` (`accepted`, `rejected`, `pending`)
- `errorCode`

## Entity: UserSession
- `accessToken`
- `user` (`id`, `name`, `email`)
- `authenticated`

## Entity: TimeSyncState
- `offsetMs`
- `lastSyncedAt`
- `now` (derived)
