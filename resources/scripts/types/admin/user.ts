import { BaseUser } from '@/types/user.ts'
import { PaginatedResult } from '@/utils/http.ts'

export interface AdminUser extends BaseUser {
    serversCount: number
    createdAt: string | null
}

export type PaginatedAdminUsers = PaginatedResult<AdminUser>

/** What the account holds across the fleet. Bytes, except `cpu` (vCPU) and the counts. */
export type UserResources = App.Data.User.UserResourcesData

/**
 * A single account as its own page shows it. The list endpoint deliberately does not carry any of
 * this — see the Optional fields on `App\Data\User\UserData`.
 */
export interface AdminUserDetail extends AdminUser {
    apiKeysCount: number
    sshKeysCount: number
    passkeysCount: number
    oauthConnectionsCount: number
    twoFactorEnabled: boolean
    lastLoginAt: string | null
    lastLoginIp: string | null
    resources: UserResources
}
