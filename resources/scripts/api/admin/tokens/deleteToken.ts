import http from '@/api/http'

const deleteToken = (id: number) => {
    return http.delete(`/api/admin/tokens/${id}`)
}

export default deleteToken