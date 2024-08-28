import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getStatistics, {
    ConsolidatorFn,
    TimeRange,
} from '@/api/servers/getStatistics.ts'


export const getKey = (
    uuid: string,
    from: TimeRange,
    consolidator: ConsolidatorFn = 'AVERAGE'
) => ['server.statistics', uuid, from, consolidator]

const useServerStatisticsSWR = (args: {
    uuid?: string
    from: TimeRange
    consolidator: ConsolidatorFn
}) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = args.uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid, args.from, args.consolidator), () =>
        getStatistics(serverUuid, args.from, args.consolidator)
    )
}

export default useServerStatisticsSWR
