import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { nodeSchema } from '@/api/admin/nodes/createNode.ts'
import { rawDataToConnectionResult } from '@/api/transformers/node.ts'

const schema = nodeSchema.pick({
    name: true,
    fqdn: true,
    port: true,
    verifyTls: true,
    tokenId: true,
    tokenSecret: true,
})

const testConnection = async ({
    name,
    fqdn,
    port,
    verifyTls,
    tokenId,
    tokenSecret,
}: z.infer<typeof schema>) => {
    const {
        data: { data },
    } = await axios.post('/api/admin/nodes/test-connection', {
        name,
        fqdn,
        port,
        verify_tls: verifyTls,
        token_id: tokenId,
        token_secret: tokenSecret,
    })

    return rawDataToConnectionResult(data)
}

export default testConnection
