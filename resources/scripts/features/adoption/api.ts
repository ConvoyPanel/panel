import GuestAdoptionController from '@/wayfinder/actions/App/Http/Controllers/Admin/GuestAdoptionController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

export type AdoptableGuest = App.Data.Server.Adoption.AdoptableGuestData
export type AdoptableGuestList = App.Data.Server.Adoption.AdoptableGuestListData
export type UnreachableScope = App.Data.Server.Adoption.UnreachableScopeData
export type AdoptionPreview = App.Data.Server.Adoption.GuestAdoptionPreviewData
export type AdoptionAddress = App.Data.Server.Adoption.AdoptionAddressData
export type AdoptionVerdict = App.Enums.Server.AddressAdoptionVerdict

// GuestAdoptionController is served under both the panel (`/api/admin`) and the
// Application token API (`/api/application`), so Wayfinder emits URI-keyed
// dictionaries instead of callables — the panel references the admin route.
const indexRoute = GuestAdoptionController.index['/api/admin/adoptable-guests']
const previewRoute =
    GuestAdoptionController.show[
        '/api/admin/nodes/{node}/adoptable-guests/{vmid}'
    ]
const adoptRoute =
    GuestAdoptionController.store[
        '/api/admin/nodes/{node}/adoptable-guests/{vmid}'
    ]

/** spatie/laravel-data wraps nested collections in their own `data` key. */
const unwrap = <T>(value: unknown): T[] => {
    if (Array.isArray(value)) return value as T[]
    if (value && typeof value === 'object' && 'data' in value) {
        return ((value as { data: T[] }).data ?? []) as T[]
    }

    return []
}

const getAdoptableGuests = async (): Promise<AdoptableGuestList> => {
    const { data } = await apiFetch<DataResponse<any>>(indexRoute())

    return {
        guests: unwrap<AdoptableGuest>(data.guests),
        unreachable: unwrap<UnreachableScope>(data.unreachable),
    }
}

const getAdoptionPreview = async (
    nodeId: number,
    vmid: number
): Promise<AdoptionPreview> => {
    const { data } = await apiFetch<DataResponse<any>>(
        previewRoute([nodeId, vmid])
    )

    return {
        ...data,
        addresses: unwrap<AdoptionAddress>(data.addresses),
        ignoredInterfaces: unwrap<string>(data.ignoredInterfaces),
    }
}

export const adoptionQueries = {
    all: () => ['admin', 'adoptable-guests'] as const,
    list: () =>
        queryOptions({
            queryKey: [...adoptionQueries.all(), 'list'] as const,
            queryFn: getAdoptableGuests,
            // Every read is a live round trip to Proxmox, and the answer stops
            // being true the moment somebody deletes a VM in the PVE web UI.
            staleTime: 0,
        }),
    preview: (nodeId: number | null, vmid: number | null) =>
        queryOptions({
            queryKey: [
                ...adoptionQueries.all(),
                'preview',
                nodeId,
                vmid,
            ] as const,
            queryFn: () => getAdoptionPreview(nodeId!, vmid!),
            enabled: nodeId != null && vmid != null,
            staleTime: 0,
        }),
}

export const useAdoptableGuests = () => useQuery(adoptionQueries.list())

export const useAdoptionPreview = (
    nodeId: number | null,
    vmid: number | null
) => useQuery(adoptionQueries.preview(nodeId, vmid))

export const adoptGuest = async (
    nodeId: number,
    vmid: number,
    input: { userId: number; name?: string | null }
): Promise<void> => {
    await apiFetch(adoptRoute([nodeId, vmid]), {
        body: {
            user_id: input.userId,
            name: input.name || null,
        },
    })
}

/** What each address verdict means, in the operator's terms. */
export const VERDICT_LABELS: Record<AdoptionVerdict, string> = {
    claim: 'Claimed',
    mint: 'Recorded and claimed',
    already_held: 'Already held',
    conflict: 'Held by another server',
    system_reserved: 'System reserved',
    admin_reserved: 'Reserved',
    unreachable: 'Not reachable here',
    ambiguous: 'Ambiguous',
    unmanaged: 'Not managed',
}

/** The three verdicts under which the server ends up holding the address. */
export const claimsAddress = (verdict: AdoptionVerdict) =>
    verdict === 'claim' || verdict === 'mint' || verdict === 'already_held'
