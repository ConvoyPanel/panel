import http from '@/api/http'

export interface Media {
    uuid: string
    name: string
    size: number
    hidden: boolean
    mounted: boolean
}

// it looks stupid but I wrote this for in the future
export const rawDataToMedia = (data: any): Media => data

const getMedia = async (serverUuid: string): Promise<Media[]> => {
    const {
        data: { data },
    } = await http.get(`/api/client/servers/${serverUuid}/settings/hardware/isos`)

    return data.map(rawDataToMedia)
}

export default getMedia
