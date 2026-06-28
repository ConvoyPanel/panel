import { AuthenticatedUser } from '@/types/user.ts'

export const rawDataToAuthenticatedUser = (data: any): AuthenticatedUser => ({
    id: data.id,
    name: data.name,
    email: data.email,
    rootAdmin: data.rootAdmin,
})
