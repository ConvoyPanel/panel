import type {
    ShareServerInput,
    Subuser,
    SubuserPermissionsInput,
} from '@/features/subusers/types.ts'
import type { UserInvite } from '@/features/users/api.ts'
import SubuserController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SubuserController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

const indexRoute = SubuserController.index
const storeRoute = SubuserController.store
const updateRoute = SubuserController.update
const destroyRoute = SubuserController.destroy

const getSubusers = async (server: string): Promise<Subuser[]> =>
    (await apiFetch<DataResponse<Subuser[]>>(indexRoute(server))).data

export const subuserQueries = {
    all: (server: string) => ['servers', server, 'subusers'] as const,
    list: (server: string) =>
        queryOptions({
            queryKey: subuserQueries.all(server),
            queryFn: () => getSubusers(server),
        }),
}

export const useSubusers = (server: string) =>
    useQuery(subuserQueries.list(server))

/**
 * Sharing with an address that has no account creates a guest and returns its invitation link.
 * The link comes back even when the panel also emailed it: mail is not proof of delivery, and an
 * install with no relay has to be able to hand it over some other way.
 */
export const shareServer = async (
    server: string,
    { email, permissions }: ShareServerInput
): Promise<{ subuser: Subuser; invite: UserInvite | null }> => {
    const response = await apiFetch<
        DataResponse<Subuser> & { invite?: UserInvite }
    >(storeRoute(server), { body: { email, permissions } })

    return { subuser: response.data, invite: response.invite ?? null }
}

export const updateSubuser = async (
    server: string,
    subuser: string,
    { permissions }: SubuserPermissionsInput
): Promise<Subuser> =>
    (
        await apiFetch<DataResponse<Subuser>>(
            updateRoute({ server, subuser }),
            { body: { permissions } }
        )
    ).data

export const removeSubuser = async (
    server: string,
    subuser: string
): Promise<void> => {
    await apiFetch(destroyRoute({ server, subuser }))
}
