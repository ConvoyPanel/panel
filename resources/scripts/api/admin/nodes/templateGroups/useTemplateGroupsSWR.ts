import useSWR from 'swr'

import { isValidNodeId } from '@/api/admin/nodes/isValidNodeId'
import getTemplateGroups, {
    TemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'

const useTemplateGroupsSWR = (
    nodeId: number,
    fallbackData?: TemplateGroup[]
) => {
    return useSWR<TemplateGroup[]>(
        // A null key tells SWR not to fetch. The create-server form renders this
        // before a node is picked, where nodeId is '' or NaN, and the request went
        // out anyway as /api/admin/nodes//template-groups -> 404.
        isValidNodeId(nodeId) ? ['admin:node:template-groups', nodeId] : null,
        () => getTemplateGroups(nodeId),
        {
            fallbackData,
        }
    )
}

export default useTemplateGroupsSWR
