export interface BaseUser {
    id: number
    name: string
    email: string
    /** Where this account's picture is served from, or null when it has none. */
    avatarUrl: string | null
    rootAdmin: boolean
}

export interface AuthenticatedUser extends BaseUser {}
