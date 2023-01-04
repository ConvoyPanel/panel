import http from '@/api/http'

const mountMedia = (serverUuid: string, mediaUuid: string) => {
    return http.post(`/api/client/servers/${serverUuid}/settings/hardware/media/${mediaUuid}/mount`)
}

export default mountMedia
