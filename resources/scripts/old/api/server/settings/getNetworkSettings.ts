import http from '@/api/http'

export interface NetworkSettings {
    nameservers: string[]
}

const getNetworkSettings = async (uuid: string): Promise<NetworkSettings> => {
    const {
        data: { data },
    } = await http.get(`/api/client/servers/${uuid}/settings/network`)

    return data
}

export default getNetworkSettings