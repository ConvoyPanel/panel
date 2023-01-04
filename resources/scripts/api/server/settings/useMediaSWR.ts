import useSWR from 'swr'
import getMedia, { Media } from '@/api/server/settings/getMedia'

const useMediaSWR = (serverUuid: string) => {
    return useSWR<Media[]>(['server:settings:hardware:media', serverUuid], () => getMedia(serverUuid))
}

export default useMediaSWR
