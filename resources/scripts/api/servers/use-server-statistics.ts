import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getStatistics, {
    ConsolidatorFn,
    TimeRange,
} from '@/api/servers/getStatistics.ts'


export const getKey = (
    uuid: string,
    from: TimeRange,
    consolidator: ConsolidatorFn = 'AVERAGE'
) => ['server.statistics', uuid, from, consolidator]

const useServerStatistics = (args: {
    uuid?: string
    from: TimeRange
    consolidator?: ConsolidatorFn
}) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = args.uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid, args.from, args.consolidator),
        queryFn: () => getStatistics(serverUuid, args.from, args.consolidator),
    })
}

export default useServerStatistics
