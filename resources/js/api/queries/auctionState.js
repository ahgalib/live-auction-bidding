export const AUCTION_STATE_QUERY = `
  query AuctionState($id: ID!) {
    auction(id: $id) {
      id
      currentPrice
      currentWinnerId
      endTime
      status
    }
  }
`;
