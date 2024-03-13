import useSWR from 'swr'

import getIsos, { Iso } from '@/api/server/settings/getIsos'

const useIsosSWR = (serverUuid: string) => {
    return useSWR<Iso[]>(['server:settings:hardware:media', serverUuid], () =>
        getIsos(serverUuid)
    )
}

export default useIsosSWR