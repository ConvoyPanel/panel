import { rawDataToUser } from '@/api/admin/users/getUsers'
import http from '@/api/http'

interface CreateUserParameters {
    name: string
    email: string
    password: string
    rootAdmin: boolean
}

const createUser = async ({ rootAdmin, ...payload }: CreateUserParameters) => {
    const {
        data: { data },
    } = await http.post(`/api/admin/users`, {
        root_admin: rootAdmin,
        ...payload,
    })

    return rawDataToUser(data)
}

export default createUser