import { useParams } from '@tanstack/react-router'
import {
    queryOptions,
    useQuery,
    type UseQueryOptions,
} from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getAddresses from '@/api/servers/getAddresses.ts'
import getServer from '@/api/servers/getServer.ts'
import getServerDeployment from '@/api/servers/getServerDeployment.ts'
import getServerResources from '@/api/servers/getServerResources'
import getState from '@/api/servers/getState.ts'
import getStatistics, {
    ConsolidatorFn,
    TimeRange,
} from '@/api/servers/getStatistics.ts'
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts'
import { Server } from '@/types/server'
import { TemplateGroup } from '@/types/template-group.ts'

export const serverQueries = {
    all: (uuid: string) => ['server', uuid] as const,
    detail: (uuid: string) =>
        queryOptions({
            queryKey: serverQueries.all(uuid),
            queryFn: () => getServer(uuid),
        }),
    resources: (uuid: string) =>
        queryOptions({
            queryKey: [...serverQueries.all(uuid), 'resources'] as const,
            queryFn: () => getServerResources(uuid),
            refetchInterval: 60000,
        }),
    deployment: (uuid: string) =>
        queryOptions({
            queryKey: [...serverQueries.all(uuid), 'deployment'] as const,
            queryFn: () => getServerDeployment(uuid),
        }),
    addresses: (uuid: string) =>
        queryOptions({
            queryKey: [...serverQueries.all(uuid), 'addresses'] as const,
            queryFn: () => getAddresses(uuid),
        }),
    state: (uuid: string) =>
        queryOptions({
            queryKey: [...serverQueries.all(uuid), 'state'] as const,
            queryFn: () => getState(uuid),
            refetchInterval: 50,
        }),
    statistics: (
        uuid: string,
        from: TimeRange,
        consolidator: ConsolidatorFn = 'AVERAGE'
    ) =>
        queryOptions({
            queryKey: [
                ...serverQueries.all(uuid),
                'statistics',
                from,
                consolidator,
            ] as const,
            queryFn: () => getStatistics(uuid, from, consolidator),
        }),
    templateGroups: (uuid: string | undefined) =>
        queryOptions({
            queryKey: [
                'server',
                uuid,
                'template-groups',
            ] as const,
            queryFn: async (): Promise<TemplateGroup[]> => {
                const { data } = await import('@/lib/axios.ts').then(m =>
                    m.default.get(
                        `/api/client/servers/${uuid}/settings/template-groups`
                    )
                )
                return data.data.map(rawDataToTemplateGroup)
            },
            enabled: !!uuid,
        }),
}

export const preloadServer = (uuid: string) =>
    queryClient.prefetchQuery(serverQueries.detail(uuid))

const useServer = (
    uuid?: string,
    options?: Partial<UseQueryOptions<Server>>
) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        ...serverQueries.detail(serverUuid),
        ...options,
    } as UseQueryOptions<Server>)
}

export default useServer
