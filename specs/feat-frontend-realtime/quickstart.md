# Quickstart: Realtime Auction Frontend

## 1. Install dependencies
```powershell
npm install
```

## 2. Run frontend
```powershell
npm run dev
```

## 3. Open auction room
- Visit `/` or `/auction/{id}`.
- Login or register from the auth panel.
- Place bids with quick controls.

## 4. Validate resilience
- Disconnect realtime transport and confirm overlay appears.
- Switch tab away and back; confirm hard re-sync occurs.

## 5. Validate optimistic behavior
- Submit a low/equal bid and confirm rollback with toast message.
