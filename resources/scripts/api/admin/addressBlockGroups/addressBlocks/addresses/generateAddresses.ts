import axios from '@/lib/axios.ts'
import { rawDataToGeneratedAddressesResult } from '@/api/transformers/address.ts'

const generateAddresses = async (blockGroupId: number, blockId: number) => {
    const { data: { data }} = await axios.post(`/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}/addresses/generate`)

    return rawDataToGeneratedAddressesResult(data)
}

export default generateAddresses