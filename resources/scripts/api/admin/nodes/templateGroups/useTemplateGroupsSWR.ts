import useSWR from 'swr'

import getTemplateGroups, {
    TemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'

const useTemplateGroupsSWR = (
    nodeId: number,
    fallbackData?: TemplateGroup[]
) => {
    return useSWR<TemplateGroup[]>(
        ['admin:node:template-groups', nodeId],
        () => getTemplateGroups(nodeId),
        {
            fallbackData,
        }
    )
}

export default useTemplateGroupsSWR