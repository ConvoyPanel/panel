import http from '@/api/http'

export interface Iso {
    uuid: string
    name: string
    size: number
    hidden: boolean
    mounted: boolean
}

// it looks stupid but I wrote this for in the future
export const rawDataToMedia = (data: any): Iso => data

const getIsos = async (serverUuid: string): Promise<Iso[]> => {
    const {
        data: { data },
    } = await http.get(
        `/api/client/servers/${serverUuid}/settings/hardware/isos`
    )

    return data.map(rawDataToMedia)
}

export default getIsos