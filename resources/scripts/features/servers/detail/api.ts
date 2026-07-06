import { useParams } from '@tanstack/react-router'
import {
    queryOptions,
    useQuery,
    type UseQueryOptions,
} from '@tanstack/react-query'

import { rawDataToAddress } from '@/lib/transformers/address.ts'
import { rawDataToServerResources } from '@/lib/transformers/server-resources'
import { rawDataToServerTimepointData } from '@/lib/transformers/server.ts'
import { rawDataToTemplateGroup } from '@/lib/transformers/template-group.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import { Address } from '@/types/address.ts'
import type { Deployment, DeploymentStep } from '@/types/deployment'
import type {
    Server,
    ServerResources,
    ServerStateData,
    ServerTimepointData,
} from '@/types/server'
import type { Template } from '@/types/template'
import { TemplateGroup } from '@/types/template-group.ts'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ServerController'
import ResourceController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ResourceController'
import StatisticController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/StatisticController'
import AddressController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/AddressController'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'

export type TimeRange = 'hour' | 'day' | 'week' | 'month' | 'year'
export type ConsolidatorFn = 'AVERAGE' | 'MAX'
export type PowerAction = 'start' | 'shutdown' | 'kill' | 'restart'

export interface ReinstallServerRequest {
    templateUuid: string
    accountPassword?: string
    startOnCompletion?: boolean
}

export const getServer = async (uuid: string): Promise<Server> => {
    const { data } = await apiFetch<DataResponse<any>>(
        ServerController.show(uuid)
    )

    return {
        id: data.id,
        uuid: data.uuid,
        uuidShort: data.uuidShort,
        nodeId: data.nodeId,
        node: undefined,
        userId: data.userId,
        vmid: data.vmid,
        hostname: data.hostname,
        name: data.name,
        description: data.description,
        status: data.status,
        cpu: data.cpu,
        memory: data.memory,
        disk: data.disk,
        backup: {
            countLimit: data.backupCountLimit,
            sizeLimit: data.backupSizeLimit,
        },
        bandwidth: {
            usage: data.bandwidthUsage,
            limit: data.bandwidthLimit,
        },
        createdAt: new Date(data.createdAt),
    }
}

export const getState = async (uuid: string): Promise<ServerStateData> => {
    const { data } = await apiFetch<DataResponse<any>>(
        ServerController.getState(uuid)
    )

    return {
        state: data.state,
        cpuUsed: data.cpuUsed,
        memoryTotal: data.memoryTotal,
        memoryUsed: data.memoryUsed,
        uptime: data.uptime,
    }
}

export const getServerResources = async (
    uuid: string
): Promise<ServerResources> => {
    const { data } = await apiFetch<DataResponse<any>>(ResourceController(uuid))

    return rawDataToServerResources(data)
}

export const getStatistics = async (
    uuid: string,
    from: TimeRange,
    consolidator: ConsolidatorFn = 'AVERAGE'
): Promise<ServerTimepointData[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(
        StatisticController(uuid),
        { params: { from, consolidator } }
    )

    return data.map(rawDataToServerTimepointData)
}

export const getAddresses = async (uuid: string): Promise<Address[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(AddressController(uuid))

    return data.map(rawDataToAddress)
}

export const getServerDeployment = async (
    uuid: string
): Promise<Deployment | null> => {
    // The endpoint returns 204 (empty body) when there is no active deployment;
    // apiFetch surfaces that as a falsy body rather than a status code.
    const raw = await apiFetch<DataResponse<any> | ''>(
        ServerController.getDeployment(uuid)
    )

    if (!raw || typeof raw !== 'object') {
        return null
    }

    const data = raw.data

    const steps: DeploymentStep[] = (data.steps?.data ?? data.steps ?? []).map(
        (step: any) => ({
            id: step.id,
            name: step.name,
            status: step.status,
            progressCurrent: step.progressCurrent,
            progressTotal: step.progressTotal,
            startedAt: step.startedAt ? new Date(step.startedAt) : null,
            completedAt: step.completedAt ? new Date(step.completedAt) : null,
            errorCode: step.errorCode,
            errorMessage: step.errorMessage,
        })
    )

    const templateData = data.template?.data ?? data.template

    return {
        id: data.id,
        serverId: data.serverId,
        templateId: data.templateId,
        status: data.status,
        type: data.type,
        startOnCompletion: data.startOnCompletion,
        requestedAt: new Date(data.requestedAt),
        completedAt: data.completedAt ? new Date(data.completedAt) : null,
        template: templateData
            ? ({
                  uuid: templateData.uuid,
                  templateGroupId: templateData.templateGroupId,
                  name: templateData.name,
                  description: templateData.description,
                  vmid: templateData.vmid,
                  isAdminOnly: templateData.isAdminOnly,
              } as Template)
            : undefined,
        steps,
    }
}

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
            queryKey: ['server', uuid, 'template-groups'] as const,
            queryFn: async (): Promise<TemplateGroup[]> => {
                const { data } = await apiFetch<DataResponse<any[]>>(
                    SettingsController.getTemplateGroups(uuid!)
                )
                return data.map(rawDataToTemplateGroup)
            },
            enabled: !!uuid,
        }),
}

export const preloadServer = (uuid: string) =>
    queryClient.prefetchQuery(serverQueries.detail(uuid))

export const useServer = (
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

export const useServerState = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery(serverQueries.state(serverUuid))
}

export const useServerResources = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery(serverQueries.resources(serverUuid))
}

export const useAddresses = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery(serverQueries.addresses(serverUuid))
}

export const useTemplateGroups = (uuid?: string) =>
    useQuery(serverQueries.templateGroups(uuid))

export const useServerDeployment = (
    uuid?: string,
    options?: Partial<UseQueryOptions<Deployment | null>>
) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        ...serverQueries.deployment(serverUuid),
        ...options,
    } as UseQueryOptions<Deployment | null>)
}

export const useServerStatistics = (args: {
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

export const updateState = async (
    uuid: string,
    state: PowerAction
): Promise<void> => {
    await apiFetch(ServerController.updateState(uuid), { body: { state } })
}

export const reinstallServer = async (
    uuid: string,
    data: ReinstallServerRequest
): Promise<void> => {
    await apiFetch(SettingsController.reinstall(uuid), {
        body: {
            template_uuid: data.templateUuid,
            account_password: data.accountPassword,
            start_on_completion: data.startOnCompletion,
        },
    })
}

export const retryInstallation = async (uuid: string): Promise<void> => {
    await apiFetch(ServerController.retryInstallation(uuid))
}
