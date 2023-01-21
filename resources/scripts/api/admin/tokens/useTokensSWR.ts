import getTokens, { QueryParams, TokenResponse } from '@/api/admin/tokens/getTokens'
import useSWR from 'swr'

const useTokensSWR = ({ page, ...params }: QueryParams) => {
    return useSWR<TokenResponse>(['admin:tokens', page], () => getTokens({ page, ...params }))
}

export default useTokensSWR
