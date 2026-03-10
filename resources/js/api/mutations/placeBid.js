export const PLACE_BID_MUTATION = `
  mutation PlaceBid($auctionId: ID!, $amount: Float!) {
    placeBid(auctionId: $auctionId, amount: $amount) {
      accepted
      currentPrice
      endTime
      errorCode
    }
  }
`;
