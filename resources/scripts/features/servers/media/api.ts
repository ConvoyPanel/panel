import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'

export interface ServerMedia {
    uuid: string
    name: string
    size: number
    hidden: boolean
    mounted: boolean
}

// getMedia returns a bare array (it's assembled in the controller, not a Data object), so there's
// no `data` envelope here.
export const getMedia = async (uuid: string): Promise<ServerMedia[]> =>
    apiFetch<ServerMedia[]>(SettingsController.getMedia(uuid))

export const mountMedia = async (
    uuid: string,
    isoUuid: string
): Promise<void> => {
    await apiFetch(SettingsController.mountMedia([uuid, isoUuid]))
}

export const unmountMedia = async (
    uuid: string,
    isoUuid: string
): Promise<void> => {
    await apiFetch(SettingsController.unmountMedia([uuid, isoUuid]))
}

export const mediaQueries = {
    all: (uuid: string) => ['servers', uuid, 'media'] as const,
    list: (uuid: string) =>
        queryOptions({
            queryKey: mediaQueries.all(uuid),
            queryFn: () => getMedia(uuid),
        }),
}

export const useMedia = (uuid: string) => useQuery(mediaQueries.list(uuid))
