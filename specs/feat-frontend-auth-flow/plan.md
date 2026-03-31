# Implementation Plan: Frontend Auth Flow & Pages

**Feature**: Frontend Authentication Flow with Landing, Login, Registration, and Bidding Pages  
**Status**: Planning  
**Branch**: `feat-frontend-auth-pages`  
**Estimated Duration**: 2-3 days  

---

## Implementation Strategy

### Phase 1: Setup & Infrastructure
**Goal**: Establish routing, state management, and API foundation

**Tasks:**
- [ ] Install Vue Router for page navigation
- [ ] Create router configuration with protected routes
- [ ] Setup Pinia auth store (user state, token, login/logout)
- [ ] Configure Axios with auth interceptors
- [ ] Create API service layer for auth endpoints
- [ ] Setup environment variables for API base URL

**Dependencies**: None (foundational)  
**Testing**: Router navigation logic, API interceptor flows  
**Deliverable**: Base structure with working routing and auth state

---

### Phase 2: Landing Page (US1)
**Goal**: Create professional landing page for unauthenticated users

**Tasks:**
- [ ] Create `LandingPage.vue` component
- [ ] Design hero section with CTA buttons
- [ ] Add feature highlights section
- [ ] Build responsive navigation bar
- [ ] Add footer with branding
- [ ] Implement responsive mobile layout
- [ ] Setup route: `/` → LandingPage

**Styling**: Tailwind CSS with modern design patterns  
**Testing**: Responsive layout tests (mobile, tablet, desktop)  
**Deliverable**: Professional landing page accessible at /

---

### Phase 3: Authentication Pages (US2 + US3)
**Goal**: Build login and registration forms with validation

**Tasks:**

**Registration Page (US2):**
- [ ] Create `RegisterPage.vue` component
- [ ] Build form with Name, Email, Password, Confirm Password fields
- [ ] Implement client-side validation (email format, password strength)
- [ ] Add visual password strength indicator
- [ ] Handle form submission to `/api/register` endpoint
- [ ] Display success toast and redirect to login
- [ ] Display error messages per field
- [ ] Add link to login page
- [ ] Setup route: `/register` → RegisterPage

**Login Page (US3):**
- [ ] Create `LoginPage.vue` component
- [ ] Build form with Email, Password, Remember Me fields
- [ ] Implement client-side validation
- [ ] Handle form submission to `/api/login` endpoint
- [ ] Store token + refresh token in localStorage/sessionStorage
- [ ] Handle "Remember Me" (7-day token persistence)
- [ ] Display error messages (generic for security)
- [ ] Add loading state during authentication
- [ ] Redirect to bidding page on success
- [ ] Add link to registration page
- [ ] Setup route: `/login` → LoginPage

**Form Components:**
- [ ] Create reusable `FormInput.vue` component
- [ ] Create reusable `FormButton.vue` component
- [ ] Create reusable `FormError.vue` component

**Testing:**
- Form validation (client-side)
- Error handling and display
- Navigation after auth
- Token storage and retrieval

---

### Phase 4: Bidding Page (US4)
**Goal**: Create the main bidding experience page

**Tasks:**
- [ ] Create `BiddingRoom.vue` component
- [ ] Display auction header with name and countdown timer
- [ ] Display current highest bid amount
- [ ] Display user's wallet balance
- [ ] Display user's current highest bid (if any)
- [ ] Build bid input form with increment buttons
- [ ] Handle bid submission to `/api/auctions/{id}/bid`
- [ ] Display bid history (last 10 bids)
- [ ] Real-time bid updates via Apollo subscriptions/Pusher
- [ ] Real-time countdown timer
- [ ] Show outbid notifications
- [ ] Add user header with name, profile, logout button
- [ ] Setup route: `/bid/:auctionId` → BiddingRoom (protected)

**Components to Create:**
- [ ] `AuctionHeader.vue` - Auction info and countdown
- [ ] `BidForm.vue` - Bid input and submission
- [ ] `BidHistory.vue` - Recent bids list
- [ ] `WalletDisplay.vue` - User balance info
- [ ] `UserHeader.vue` - User profile and logout

**Real-Time Integration:**
- [ ] Setup WebSocket connection for bid updates
- [ ] Listen to BidUpdated events
- [ ] Update bid history in real-time
- [ ] Handle disconnection gracefully

**Testing:**
- Bid submission flow
- Real-time updates
- Error handling (insufficient balance, invalid bid amount)
- User session validation

---

### Phase 5: Session Management (US5)
**Goal**: Ensure persistent and secure sessions

**Tasks:**
- [ ] Implement token refresh logic in auth store
- [ ] Setup axios interceptors for 401 responses
- [ ] Create route guard for protected pages
- [ ] Handle token expiration gracefully
- [ ] Implement logout functionality
- [ ] Clear localStorage on logout
- [ ] Auto-redirect to login on unauthorized access
- [ ] Test session persistence across page reloads

**Testing:**
- Token expiration handling
- Page navigation with/without token
- Protected route access control

---

## API Contract Reference

See `contracts/api-contract.md` for detailed endpoint specifications

**Key Endpoints to Verify/Implement:**
- POST `/api/register` ← backend
- POST `/api/login` ← backend
- GET `/api/me` ← backend
- POST `/api/logout` ← backend
- GET `/api/auctions/{id}` ← already exists
- POST `/api/auctions/{id}/bid` ← already exists

---

## Backend Changes (If Needed)

**Verify existing:**
- [ ] LoginController/AuthController exists
- [ ] Register endpoint validates and creates users
- [ ] Login endpoint returns JWT or token
- [ ] Logout endpoint invalidates sessions
- [ ] /me endpoint returns authenticated user data
- [ ] OAuth middleware securing protected routes

**May Need to Create:**
- [ ] Proper error response format (consistent)
- [ ] CORS configuration for frontend domain
- [ ] Rate limiting on auth endpoints
- [ ] Token refresh endpoint (optional)

---

## File Structure

```
Front-End Project (front-end/)
src/
├── router/
│   └── index.js                  # Vue Router config with routes & guards
├── stores/
│   └── auth.js                   # Pinia auth store (user, token, login/logout)
├── services/
│   ├── api.js                    # Axios instance with interceptors
│   └── authService.js            # Auth API calls
├── views/
│   ├── LandingPage.vue           # US1
│   ├── RegisterPage.vue          # US2
│   ├── LoginPage.vue             # US3
│   ├── BiddingRoom.vue           # US4
│   └── errors/
│       └── NotFound.vue          # 404 page
├── components/
│   ├── common/
│   │   ├── FormInput.vue
│   │   ├── FormButton.vue
│   │   ├── FormError.vue
│   │   └── UserHeader.vue
│   ├── auth/
│   │   ├── LoginForm.vue
│   │   └── RegisterForm.vue
│   └── auction/
│       ├── AuctionHeader.vue
│       ├── BidForm.vue
│       ├── BidHistory.vue
│       └── WalletDisplay.vue
├── App.vue                       # Updated: routing and layout
└── main.js                       # Updated: add Router

Backend (test-project-1/)
app/Http/Controllers/
├── AuthController.php            # Verify: register, login, logout, me
└── AuctionBidController.php      # Already exists
```

---

## Testing Strategy

### Unit Tests
- Auth store mutations and actions
- Form validation logic
- API service methods
- Router guards

### Integration Tests
- Login → redirect to bidding
- Register → validation errors
- Bid submission → balance update
- Real-time bid updates

### Manual Testing
- Page navigation and route guards
- Form submissions with valid/invalid data
- Session persistence (F5 refresh)
- Real-time bid updates
- Responsive design (mobile viewport)

---

## Success Criteria

✅ User can register with validation  
✅ User can login with token persistence  
✅ User cannot access `/bid` without login (redirects to `/login`)  
✅ Landing page is accessible and professional-looking  
✅ Bidding room displays auctions and real-time updates  
✅ User can place bids and see balance update  
✅ Logout clears session and redirects to landing  
✅ All forms have proper error handling  
✅ Responsive design works on mobile/tablet/desktop  
✅ Real-time features work with WebSocket/Pusher  

---

## Dependencies to Install

```bash
cd front-end
npm install vue-router
npm install axios
npm install pinia  # already installed
npm install tailwindcss  # already installed
```

---

## Timeline

- **Phase 1** (Setup): ~2-3 hours
- **Phase 2** (Landing Page): ~2-3 hours
- **Phase 3** (Auth Pages): ~4-5 hours
- **Phase 4** (Bidding Page): ~4-5 hours
- **Phase 5** (Session Management): ~2-3 hours

**Total**: ~14-20 hours

---

## Next Steps

1. Execute Phase 1: Router and Pinia setup
2. Create all components and pages in sequence
3. Test each phase before moving to next
4. Integrate backend APIs
5. Manual testing and bug fixes
6. Responsive design polish
