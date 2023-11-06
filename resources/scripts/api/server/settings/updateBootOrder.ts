import http from '@/api/http'

export default (uuid: string, order: string[]) => {
    return http.put(
        `/api/client/servers/${uuid}/settings/hardware/boot-order`,
        {
            order,
        }
    )
}