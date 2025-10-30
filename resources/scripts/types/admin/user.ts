import { BaseUser } from '@/types/user.ts'
import { PaginatedResult } from '@/utils/http.ts'

export interface AdminUser extends BaseUser {
    serversCount: number
}

export type PaginatedAdminUsers = PaginatedResult<AdminUser>
