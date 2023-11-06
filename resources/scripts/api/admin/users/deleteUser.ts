import http from '@/api/http'

const deleteUser = (id: number) => {
    return http.delete(`/api/admin/users/${id}`)
}

export default deleteUser