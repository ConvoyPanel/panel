import http from '@/api/http'

const unmountMissingMedia = (serverUuid: string, diskInterface: string) => {
    return http.post(
        `/api/client/servers/${serverUuid}/settings/hardware/drives/${diskInterface}/unmount`
    )
}

export default unmountMissingMedia
