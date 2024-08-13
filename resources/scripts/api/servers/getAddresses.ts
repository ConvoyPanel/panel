import { Address } from '@/types/address.ts'

import axios from '@/lib/axios.ts'

import { rawDataToAddress } from '@/api/transformers/address.ts'


const getAddresses = async (uuid: string): Promise<Address[]> => {
    const {
        data: { data },
    } = await axios.get(`/api/client/servers/${uuid}/addresses`)

    return data.map(rawDataToAddress)
}

export default getAddresses
