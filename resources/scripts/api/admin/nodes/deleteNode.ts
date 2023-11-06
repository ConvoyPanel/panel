import http from '@/api/http'

const deleteNode = (id: number) => http.delete(`/api/admin/nodes/${id}`)

export default deleteNode