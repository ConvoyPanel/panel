export interface BaseUser {
    id: number
    name: string
    email: string
    rootAdmin: boolean
}

export interface AuthenticatedUser extends BaseUser {}
