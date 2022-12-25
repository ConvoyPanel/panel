import getTemplates, { TemplateGroup } from '@/api/admin/nodes/templates/getTemplates'
import useSWR from 'swr'

const useTemplatesSWR = (nodeId: number) => {
    return useSWR<TemplateGroup[]>(['admin:node:templates', nodeId], () => getTemplates(nodeId))
}

export default useTemplatesSWR
