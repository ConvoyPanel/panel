import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { ConsolidatorFn, TimeRange } from '@/api/servers/getStatistics.ts'
import { serverQueries } from '@/api/servers/use-server.ts'

const useServerStatistics = (args: {
    uuid?: string
    from: TimeRange
    consolidator?: ConsolidatorFn
}) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = args.uuid ?? params.serverUuid

    return useQuery(
        serverQueries.statistics(serverUuid, args.from, args.consolidator)
    )
}

export default useServerStatistics
