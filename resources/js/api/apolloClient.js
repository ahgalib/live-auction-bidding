import { ApolloClient, InMemoryCache, HttpLink } from '@apollo/client/core';

const uri = import.meta.env.VITE_GRAPHQL_ENDPOINT || '/graphql';

export const apolloClient = new ApolloClient({
    link: new HttpLink({ uri }),
    cache: new InMemoryCache(),
});
