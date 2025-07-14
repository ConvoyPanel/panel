import useSWRMutation from 'swr/mutation'
import { toast } from 'sonner'

import deleteTemplate from '@/api/admin/templateGroups/templates/deleteTemplate.ts'
import { getKey } from '@/api/admin/templateGroups/templates/use-templates-swr.ts'
import { Template } from '@/types/template.ts'

const useDeleteTemplateMutation = (templateGroupUuid: string) => {
    return useSWRMutation(
        getKey(templateGroupUuid, {}),
        async (_, { arg: templateUuid }: { arg: string }) => {
            await deleteTemplate(templateGroupUuid, templateUuid)
            return templateUuid
        },
        {
            onSuccess: () => {
                toast.success('Template deleted successfully')
            },
            onError: () => {
                toast.error('Failed to delete template')
            },
            populateCache: (templateUuid: string, currentData: Template[] | undefined) => {
                if (!currentData) return []
                return currentData.filter(t => t.uuid !== templateUuid)
            },
            revalidate: false, // Don't revalidate since we're using populateCache
        }
    )
}

export default useDeleteTemplateMutation
