import { AdminUser, AdminUserDetail } from '@/types/admin/user.ts'

export const rawDataToAdminUser = (data: any): AdminUser => ({
    id: data.id,
    name: data.name,
    email: data.email,
    avatarUrl: data.avatarUrl ?? null,
    rootAdmin: data.rootAdmin,
    serversCount: data.serversCount,
    createdAt: data.createdAt ?? null,
})

/**
 * The detail endpoint's richer payload. The counts default to zero rather than being left
 * undefined: they are rendered straight into the page, and a missing count should read as "none",
 * never as an empty slot in a card.
 */
export const rawDataToAdminUserDetail = (data: any): AdminUserDetail => ({
    ...rawDataToAdminUser(data),
    apiKeysCount: data.apiKeysCount ?? 0,
    sshKeysCount: data.sshKeysCount ?? 0,
    passkeysCount: data.passkeysCount ?? 0,
    oauthConnectionsCount: data.oauthConnectionsCount ?? 0,
    twoFactorEnabled: data.twoFactorEnabled ?? false,
    lastLoginAt: data.lastLoginAt ?? null,
    lastLoginIp: data.lastLoginIp ?? null,
    resources: data.resources ?? {
        serversCount: 0,
        suspendedCount: 0,
        unbuiltCount: 0,
        nodesCount: 0,
        cpu: 0,
        memory: 0,
        disk: 0,
        bandwidthUsage: 0,
        bandwidthLimit: 0,
    },
})
