import type { ApiKey } from '@/features/account/api-keys/api.ts'
import type { OAuthConnection } from '@/features/account/oauth/api.ts'
import { rawDataToPasskey } from '@/features/account/transforms.ts'
import type { Passkey, SSHKey } from '@/features/account/types.ts'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import UserCredentialController from '@/wayfinder/actions/App/Http/Controllers/Admin/UserCredentialController'

/*
 * UserCredentialController is served under both `/api/admin` and `/api/application`, so Wayfinder
 * emits URI-keyed dictionaries rather than plain functions — reference the admin URI explicitly,
 * the same way features/tokens does.
 */
const routes = {
    apiKeys: UserCredentialController.apiKeys['/api/admin/users/{user}/api-keys'],
    destroyApiKey:
        UserCredentialController.destroyApiKey[
            '/api/admin/users/{user}/api-keys/{apiKey}'
        ],
    sshKeys: UserCredentialController.sshKeys['/api/admin/users/{user}/ssh-keys'],
    destroySshKey:
        UserCredentialController.destroySshKey[
            '/api/admin/users/{user}/ssh-keys/{sshKey}'
        ],
    passkeys: UserCredentialController.passkeys['/api/admin/users/{user}/passkeys'],
    destroyPasskey:
        UserCredentialController.destroyPasskey[
            '/api/admin/users/{user}/passkeys/{passkey}'
        ],
    oauthConnections:
        UserCredentialController.oauthConnections[
            '/api/admin/users/{user}/oauth-connections'
        ],
    destroyOauthConnection:
        UserCredentialController.destroyOauthConnection[
            '/api/admin/users/{user}/oauth-connections/{oauthConnection}'
        ],
    destroyTwoFactor:
        UserCredentialController.destroyTwoFactor[
            '/api/admin/users/{user}/two-factor'
        ],
}

const rawDataToApiKey = (data: App.Data.User.ApiKeyData): ApiKey => ({
    id: data.id,
    name: data.name,
    abilities: data.abilities,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
})

const rawDataToSSHKey = (data: App.Data.User.SSHKeyData): SSHKey => ({
    id: data.id,
    name: data.name,
    publicKey: data.publicKey,
    createdAt: new Date(data.createdAt),
})

const rawDataToConnection = (
    data: App.Data.User.OAuthConnectionData
): OAuthConnection => ({
    id: data.id,
    provider: data.provider,
    label: data.label,
    name: data.name,
    email: data.email,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
    createdAt: new Date(data.createdAt),
})

export const getUserApiKeys = async (userId: number): Promise<ApiKey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.ApiKeyData[]>>(
        routes.apiKeys(userId)
    )

    return data.map(rawDataToApiKey)
}

export const getUserSSHKeys = async (userId: number): Promise<SSHKey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.SSHKeyData[]>>(
        routes.sshKeys(userId)
    )

    return data.map(rawDataToSSHKey)
}

export const getUserPasskeys = async (userId: number): Promise<Passkey[]> => {
    const { data } = await apiFetch<DataResponse<App.Data.User.PasskeyData[]>>(
        routes.passkeys(userId)
    )

    return data.map(rawDataToPasskey)
}

export const getUserOAuthConnections = async (
    userId: number
): Promise<OAuthConnection[]> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.User.OAuthConnectionData[]>
    >(routes.oauthConnections(userId))

    return data.map(rawDataToConnection)
}

export const revokeUserApiKey = async (userId: number, id: number) => {
    await apiFetch(routes.destroyApiKey([userId, id]))
}

export const revokeUserSSHKey = async (userId: number, id: number) => {
    await apiFetch(routes.destroySshKey([userId, id]))
}

export const revokeUserPasskey = async (userId: number, id: number) => {
    await apiFetch(routes.destroyPasskey([userId, id]))
}

export const revokeUserOAuthConnection = async (userId: number, id: number) => {
    await apiFetch(routes.destroyOauthConnection([userId, id]))
}

export const disableUserTwoFactor = async (userId: number) => {
    await apiFetch(routes.destroyTwoFactor(userId))
}

export const userCredentialQueries = {
    all: (userId: number) => ['admin', 'users', userId, 'credentials'] as const,
    apiKeys: (userId: number) =>
        queryOptions({
            queryKey: [...userCredentialQueries.all(userId), 'api-keys'] as const,
            queryFn: () => getUserApiKeys(userId),
        }),
    sshKeys: (userId: number) =>
        queryOptions({
            queryKey: [...userCredentialQueries.all(userId), 'ssh-keys'] as const,
            queryFn: () => getUserSSHKeys(userId),
        }),
    passkeys: (userId: number) =>
        queryOptions({
            queryKey: [...userCredentialQueries.all(userId), 'passkeys'] as const,
            queryFn: () => getUserPasskeys(userId),
        }),
    oauthConnections: (userId: number) =>
        queryOptions({
            queryKey: [
                ...userCredentialQueries.all(userId),
                'oauth-connections',
            ] as const,
            queryFn: () => getUserOAuthConnections(userId),
        }),
}

export const useUserApiKeys = (userId: number) =>
    useQuery(userCredentialQueries.apiKeys(userId))

export const useUserSSHKeys = (userId: number) =>
    useQuery(userCredentialQueries.sshKeys(userId))

export const useUserPasskeys = (userId: number) =>
    useQuery(userCredentialQueries.passkeys(userId))

export const useUserOAuthConnections = (userId: number) =>
    useQuery(userCredentialQueries.oauthConnections(userId))
