import useSWR from '@/lib/swr'

import getServer from '@/api/admin/servers/getServer.ts'

export const getKey = (id: number) => ['server', id]

const useServerSWR = (id: number | null) => {
    return useSWR(
        id ? getKey(id) : null,
        () => getServer(id!)
    )
}

export default useServerSWR