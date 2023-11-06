import http from '@/api/http'

export default (uuid: string, backup: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/backups/${backup}/restore`)
            .then(() => resolve())
            .catch(reject)
    })
}