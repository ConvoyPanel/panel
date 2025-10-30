import { AdminUser } from '@/types/admin/user.ts'

export const rawDataToAdminUser = (data: any): AdminUser => ({
    id: data.id,
    name: data.name,
    email: data.email,
    rootAdmin: data.root_admin,
    serversCount: data.servers_count,
})
