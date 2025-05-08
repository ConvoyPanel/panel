import { useParams } from '@tanstack/react-router'
import useSWR, { preload } from 'swr'

import getAddressBlockGroup from '@/api/admin/addressBlockGroups/getAddressBlockGroup.ts'

export const getKey = (id: number) => ['address-block-group', id]

export const preloadAddressBlockGroup = async (id: number) => {
    await preload(getKey(id), () => getAddressBlockGroup(id))
}

const useAddressBlockGroupSWR = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
    }

    return useSWR(getKey(params.addressBlockGroupId), () =>
        getAddressBlockGroup(params.addressBlockGroupId)
    )
}

export default useAddressBlockGroupSWR
