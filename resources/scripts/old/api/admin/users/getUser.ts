import { rawDataToUser } from '@/api/admin/users/getUsers'
import http from '@/api/http'

const getUser = async (id: number) => {
    const {
        data: { data },
    } = await http.get(`/api/admin/users/${id}`)

    return rawDataToUser(data)
}

export default getUser