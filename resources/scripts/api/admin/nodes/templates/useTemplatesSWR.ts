import getTemplates, { TemplateGroup } from '@/api/admin/nodes/templates/getTemplates'
import useSWR from 'swr'

const useTemplatesSWR = (nodeId: number, fallbackData?: TemplateGroup[]) => {
    return useSWR<TemplateGroup[]>(['admin:node:templates', nodeId], () => getTemplates(nodeId), {
        fallbackData,
    })
}

export default useTemplatesSWR
