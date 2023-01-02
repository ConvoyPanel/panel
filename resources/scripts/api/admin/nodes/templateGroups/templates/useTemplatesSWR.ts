import { Template } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import useSWR from 'swr'
import getTemplates from '@/api/admin/nodes/templateGroups/templates/getTemplates'

const useTemplatesSWR = (nodeId: number, groupUuid: string) => {
    return useSWR<Template[]>(['admin:node:template-group:templates', nodeId, groupUuid], () =>
        getTemplates(nodeId, groupUuid)
    )
}

export default useTemplatesSWR
