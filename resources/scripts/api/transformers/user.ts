import { AuthenticatedUser } from '@/types/user.ts'

export const transformAuthenticatedUser = (data: any): AuthenticatedUser => ({
    id: data.id,
    name: data.name,
    email: data.email,
    rootAdmin: data.root_admin,
})
