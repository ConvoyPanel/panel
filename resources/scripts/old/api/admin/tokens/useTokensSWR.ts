import useSWR from 'swr'

import getTokens, {
    QueryParams,
    TokenResponse,
} from '@/api/admin/tokens/getTokens'

const useTokensSWR = ({ page, ...params }: QueryParams) => {
    return useSWR<TokenResponse>(['admin:tokens', page], () =>
        getTokens({ page, ...params })
    )
}

export default useTokensSWR