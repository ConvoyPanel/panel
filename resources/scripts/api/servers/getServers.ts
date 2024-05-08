import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'


const getServers = async (params: QueryBuilderParams<'name'>) => {
    const { data } = await axios.get('/api/client/servers', {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.data.map(rawDataToServer),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getServers
