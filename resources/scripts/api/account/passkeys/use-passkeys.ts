import { queryOptions, useQuery } from '@tanstack/react-query'

import getPasskeys from '@/api/account/passkeys/getPasskeys.ts'

export const passkeyQueries = {
    all: () => ['account', 'passkeys'] as const,
    list: () =>
        queryOptions({ queryKey: passkeyQueries.all(), queryFn: getPasskeys }),
}

const usePasskeys = () => useQuery(passkeyQueries.list())

export default usePasskeys
