import axios from '@/lib/axios.ts'

import { transformAuthenticatedUser } from '@/api/transformers/user.ts'


const getUser = async () => {
    const {
        data: { data },
    } = await axios.get('/api/client/user')

    return transformAuthenticatedUser(data)
}

export default getUser
