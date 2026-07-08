import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'

/** The server's configured DNS nameservers (pushed to cloud-init). */
export const getNameservers = async (uuid: string): Promise<string[]> => {
    const { data } = await apiFetch<DataResponse<{ nameservers: string[] }>>(
        SettingsController.getNetworkSettings(uuid)
    )

    return data.nameservers
}

export const updateNameservers = async (
    uuid: string,
    nameservers: string[]
): Promise<void> => {
    await apiFetch(SettingsController.updateNetworkSettings(uuid), {
        body: { nameservers },
    })
}

export const nameserverQueries = {
    all: (uuid: string) => ['servers', uuid, 'nameservers'] as const,
    detail: (uuid: string) =>
        queryOptions({
            queryKey: nameserverQueries.all(uuid),
            queryFn: () => getNameservers(uuid),
        }),
}

export const useNameservers = (uuid: string) =>
    useQuery(nameserverQueries.detail(uuid))
