import useSWR from 'swr'

import getIsos, {
    IsoResponse,
    QueryParams,
} from '@/api/admin/nodes/isos/getIsos'

const useIsosSWR = ({ page, nodeId, ...params }: QueryParams) => {
    return useSWR<IsoResponse>(['admin:node:isos', nodeId, page], () =>
        getIsos({ page, nodeId, ...params })
    )
}

export default useIsosSWR