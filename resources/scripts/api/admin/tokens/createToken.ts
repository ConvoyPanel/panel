import http from '@/api/http'
import { rawDataToToken } from '@/api/admin/tokens/getTokens'

interface CreateTokenParameters {
    name: string
}

const createToken = async (payload: CreateTokenParameters) => {
    const {
        data: { data },
    } = await http.post('/api/admin/tokens', payload)

    return rawDataToToken(data)
}

export default createToken
