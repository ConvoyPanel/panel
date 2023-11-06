import http from '@/api/http'

export default (id: number) => http.delete(`/api/admin/locations/${id}`)