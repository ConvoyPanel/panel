import { AdminUser } from '@/types/admin/user.ts'
import axios from '@/lib/axios.ts'

import { rawDataToAdminUser } from '@/api/transformers/admin/user.ts'

const getUser = async (id: number): Promise<AdminUser> => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/users/${id}`)

    return rawDataToAdminUser(data)
}

export default getUser
