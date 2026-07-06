import { useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'

import deleteTemplate from '@/api/admin/templateGroups/templates/deleteTemplate.ts'
import { templateQueries } from '@/api/admin/templateGroups/templates/use-templates.ts'
import { Template } from '@/types/template.ts'

const useDeleteTemplateMutation = (templateGroupUuid: string) => {
    const queryClient = useQueryClient()

    const mutation = useMutation({
        mutationFn: async (templateUuid: string) => {
            await deleteTemplate(templateGroupUuid, templateUuid)
            return templateUuid
        },
        onSuccess: templateUuid => {
            queryClient.setQueryData<Template[]>(
                templateQueries.list(templateGroupUuid, {}).queryKey,
                current =>
                    current
                        ? current.filter(t => t.uuid !== templateUuid)
                        : []
            )
            toast.success('Template deleted successfully')
        },
        onError: () => {
            toast.error('Failed to delete template')
        },
    })

    return {
        trigger: (templateUuid: string) => mutation.mutateAsync(templateUuid),
        isMutating: mutation.isPending,
    }
}

export default useDeleteTemplateMutation
