import axios from '@/lib/axios.ts'

import { rawDataToServerTimepointData } from '@/api/transformers/server.ts'


export type TimeRange = 'hour' | 'day' | 'week' | 'month' | 'year'
export type ConsolidatorFn = 'AVERAGE' | 'MAX'

const getStatistics = async (
    uuid: string,
    from: TimeRange,
    consolidator: ConsolidatorFn = 'AVERAGE'
) => {
    const {
        data: { data },
    } = await axios.get(`/api/client/servers/${uuid}/statistics`, {
        params: {
            from,
            consolidator,
        },
    })

    return data.map(rawDataToServerTimepointData)
}

export default getStatistics
