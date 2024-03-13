import { rawDataToUser } from '@/api/admin/users/getUsers'
import http from '@/api/http'

interface UpdateUserParameters {
    name: string
    email: string
    password?: string | null
    rootAdmin: boolean
}

const updateUser = async (
    userId: number,
    { rootAdmin, ...payload }: UpdateUserParameters
) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/users/${userId}`, {
        root_admin: rootAdmin,
        ...payload,
    })

    return rawDataToUser(data)
}

export default updateUser