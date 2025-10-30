import axios from '@/lib/axios.ts'

import { rawDataToAuthenticatedUser } from '@/api/transformers/user.ts'


const getUser = async () => {
    const {
        data: { data },
    } = await axios.get('/api/client/user')

    return rawDataToAuthenticatedUser(data)
}

export default getUser
