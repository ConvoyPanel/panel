import http from '@/api/http'

const unsuspendServer = (uuid: string) =>
    http.post(`/api/admin/servers/${uuid}/settings/unsuspend`)

export default unsuspendServer