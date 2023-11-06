import http from '@/api/http'

const suspendServer = (uuid: string) =>
    http.post(`/api/admin/servers/${uuid}/settings/suspend`)

export default suspendServer