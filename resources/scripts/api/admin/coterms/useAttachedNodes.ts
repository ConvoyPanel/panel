import useSWR from 'swr'

import getAttachedNodes, {
    QueryParams,
} from '@/api/admin/coterms/getAttachedNodes'


const useAttachedNodes = (
    id: number,
    { page, query, ...params }: QueryParams
) => {
    return useSWR(['admin.coterms.attachedNodes', id, page, query], () =>
        getAttachedNodes(id, {
            page,
            query,
            ...params,
        })
    )
}

export default useAttachedNodes