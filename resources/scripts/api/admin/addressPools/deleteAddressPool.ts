import http from '@/api/http'

const deleteAddressPool = (id: number) =>
    http.delete(`/api/admin/address-pools/${id}`)

export default deleteAddressPool