import axios from '@/lib/axios.ts'

const deleteAddressBlockGroup = async (id: number) => {
    await axios.delete(`/api/admin/address-block-groups/${id}`)
}

export default deleteAddressBlockGroup
