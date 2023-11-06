import http from '@/api/http'

export interface SecuritySettings {
    sshKeys: string
}

const rawDataToSecurityObject = (data: any): SecuritySettings => ({
    sshKeys: data.ssh_keys,
})

export default async (uuid: string): Promise<SecuritySettings> => {
    const {
        data: { data },
    } = await http.get(`/api/client/servers/${uuid}/settings/security`)

    return rawDataToSecurityObject(data)
}
