🌀 Vue 3 Frontend Documentation: Real-Time Auction Engine1. Core Architecture StackFramework: Vue 3 (Composition API + <script setup>).State Management: Pinia (Modular stores for Auctions, User Wallet, and Global Sync).GraphQL Client: Apollo Client (with @vue/apollo-composable).Real-Time Realism: Laravel Echo + Pusher JS.Utilities: date-fns (time manipulation) and Tailwind CSS.2. Global Strategy: High-Precision Time SyncOn shared hosting, the server time and the user's phone time will differ. We solve this with a Global Sync Hook.The useServerTime ComposableSyncing: On app mount, fetch the server's UTC timestamp via a GraphQL Query.Offset Calculation: const offset = serverTimestamp - Date.now().Reactive "Now": Export a computed property now that returns Date.now() + offset.3. Component Architecture (Atomic Design)We break the UI into Dumb (Presentational) and Smart (Logic-Heavy) components to ensure reusability.A. Core ComponentsComponentResponsibilityPerformance OptimizationAuctionCountdown.vueDisplays remaining time (HH:MM:SS:ms).Uses requestAnimationFrame to avoid UI jank; throttles updates to 10ms.BidActionPad.vueInput and Quick-Bid buttons (+$10, +$50).Optimistic UI: Disables button immediately on click to prevent "Double-Tapping."LivePriceDisplay.vueBig bold price display.Uses a Transition Group to animate the price "sliding up" when it changes.ParticipationBadge.vueShows "X Live Bidders".Listens to Presence Channels via Echo.4. Real-Time State Management (Pinia)We use a useAuctionStore to act as the single source of truth for the active auction.JavaScript// Example Pinia Store Logic
export const useAuctionStore = defineStore('auction', {
  state: () => ({
    currentPrice: 0,
    endTime: null,
    bids: [],
    isActive: false
  }),
  actions: {
    subscribeToAuction(id) {
      window.Echo.private(`auction.${id}`)
        .listen('.BidPlaced', (e) => {
          this.currentPrice = e.amount;
          this.endTime = e.endTime; // Handle Anti-Sniping extension
          this.bids.unshift(e.bid);
        });
    }
  }
})
5. Optimization & Best Practices1. Component Memoization & ThrottlingIf 100 people bid at once, we don't want Vue to re-render the entire page 100 times.Solution: Use v-once for static auction descriptions.Solution: Throttle the "Bid Feed" updates using a buffer—collect bids for 200ms and then push them to the array at once.2. Visibility API HandlingWhen a user switches tabs on their phone, the browser pauses JavaScript.Best Practice: When the tab becomes visible again (document.visibilityState === 'visible'), the app must immediately trigger a "Hard Re-sync" query to catch up on missed bids.3. Optimistic UI UpdatesTo make the app feel "Instant":When a user bids, add the bid to the local bids array immediately with a loading: true state.If the GraphQL Mutation returns an error (e.g., BID_TOO_LOW), roll back the local change and show a toast notification.6. Frontend File StructurePlaintextsrc/
├── api/
│   ├── queries/         # GraphQL Query strings
│   └── mutations/       # placeBid, etc.
├── components/
│   ├── auction/         # Auction-specific components
│   └── shared/          # Base buttons, inputs, modals
├── composables/
│   ├── useServerTime.js # The clock sync logic
│   └── useAuctionSubscription.js
├── stores/
│   └── auctionStore.js  # Pinia state
└── views/
    └── AuctionRoom.vue  # The main "War Room" layout
7. Frontend Checklist (frontend-checklist.md)[ ] Accessibility: Screen readers correctly announce when a user is "Outbid."[ ] Responsiveness: The "Bid Pad" is large and thumb-friendly on mobile devices.[ ] Connection Resilience: UI shows a "Connection Lost" overlay if Pusher disconnects.[ ] Sound Effects: Low-latency AudioContext used for "Chime" (Win) and "Buzz" (Outbid).