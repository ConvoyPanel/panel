import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { apiFetch, type DataResponse } from '@/lib/api'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'

/** The authorized SSH keys currently on the server (full OpenSSH public-key strings). */
export const getServerSSHKeys = async (uuid: string): Promise<string[]> => {
    const { data } = await apiFetch<DataResponse<{ sshKeys: string[] }>>(
        SettingsController.getAuthSettings(uuid)
    )

    return data.sshKeys
}

/** Replaces the server's authorized key set (the backend takes the full newline-joined list). */
export const updateServerSSHKeys = async (
    uuid: string,
    keys: string[]
): Promise<void> => {
    await apiFetch(SettingsController.updateAuthSettings(uuid), {
        body: { type: 'ssh_keys', ssh_keys: keys.join('\n') },
    })
}

export const passwordSchema = z.object({
    password: z.string().min(8).max(191),
})

export type PasswordInput = z.infer<typeof passwordSchema>

export const updateServerPassword = async (
    uuid: string,
    password: string
): Promise<void> => {
    await apiFetch(SettingsController.updateAuthSettings(uuid), {
        body: { type: 'password', password },
    })
}

export const serverSSHKeyQueries = {
    all: (uuid: string) => ['servers', uuid, 'ssh-keys'] as const,
    detail: (uuid: string) =>
        queryOptions({
            queryKey: serverSSHKeyQueries.all(uuid),
            queryFn: () => getServerSSHKeys(uuid),
        }),
}

export const useServerSSHKeys = (uuid: string) =>
    useQuery(serverSSHKeyQueries.detail(uuid))

/** Splits a `<algorithm> <base64> [comment]` key into its algorithm and (optional) comment. */
export const describeSSHKey = (
    key: string
): { algorithm: string; comment: string | null } => {
    const [algorithm, , ...rest] = key.trim().split(/\s+/)
    return {
        algorithm: algorithm ?? 'ssh-key',
        comment: rest.length ? rest.join(' ') : null,
    }
}
