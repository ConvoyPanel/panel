import http from '@/api/http'

const deleteCoterm = (id: string) => http.delete(`/admin/coterms/${id}`)

export default deleteCoterm