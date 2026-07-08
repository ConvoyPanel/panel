import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { SSHKey } from '@/features/account/types.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import SSHKeyController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/SSHKeyController'

export const sshKeyCreateSchema = z.object({
    name: z.string().min(1).max(40),
    publicKey: z.string().min(1),
})

export type SSHKeyCreateInput = z.infer<typeof sshKeyCreateSchema>

const rawDataToSSHKey = (data: App.Data.User.SSHKeyData): SSHKey => ({
    id: data.id,
    name: data.name,
    publicKey: data.publicKey,
    createdAt: new Date(data.createdAt),
})

export const getSSHKeys = async (): Promise<SSHKey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.SSHKeyData[]>>(
        SSHKeyController.index()
    )

    return data.map(rawDataToSSHKey)
}

export const createSSHKey = async ({
    name,
    publicKey,
}: SSHKeyCreateInput): Promise<SSHKey> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.SSHKeyData>>(
        SSHKeyController.store(),
        { body: { name, public_key: publicKey } }
    )

    return rawDataToSSHKey(data)
}

export const deleteSSHKey = async (id: number): Promise<void> => {
    await apiFetch(SSHKeyController.destroy(id))
}

export const sshKeyQueries = {
    all: () => ['account', 'ssh-keys'] as const,
    list: () =>
        queryOptions({ queryKey: sshKeyQueries.all(), queryFn: getSSHKeys }),
}

export const useSSHKeys = () => useQuery(sshKeyQueries.list())

/** The algorithm token of an OpenSSH public key, e.g. `ssh-ed25519`, for a small type badge. */
export const sshKeyAlgorithm = (publicKey: string): string =>
    publicKey.trim().split(/\s+/)[0] ?? 'ssh-key'
