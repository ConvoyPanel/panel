import http from '@/api/http'

export interface AuthSettings {
    sshKeys: string
}

const rawDataToAuthSettings = (data: any): AuthSettings => ({
    sshKeys: data.ssh_keys,
})

const getAuthSettings = async (uuid: string): Promise<AuthSettings> => {
    const {
        data: { data },
    } = await http.get(`/api/client/servers/${uuid}/settings/auth`)

    return rawDataToAuthSettings(data)
}

export default getAuthSettings