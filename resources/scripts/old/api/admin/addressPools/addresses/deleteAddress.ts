import http from '@/api/http'

const deleteAddress = (poolId: number, addressId: number) =>
    http.delete(`/api/admin/address-pools/${poolId}/addresses/${addressId}`)

export default deleteAddress