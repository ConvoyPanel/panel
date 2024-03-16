import useSWR, { mutate } from 'swr'

import getUser from '@/api/auth/getUser.ts'


export const getKey = () => 'user'

export const cacheUser = async () => {
    const user = await getUser()

    await mutate(getKey(), user, false)
}

const useUserSWR = () => {
    return useSWR(getKey(), () => getUser())
}

export default useUserSWR
