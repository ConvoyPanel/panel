import {
    keepPreviousData,
    queryOptions,
    useMutation,
    useQuery,
    useQueryClient,
} from '@tanstack/react-query'
import { toast } from 'sonner'
import { z } from 'zod'

import { rawDataToTemplate } from '@/api/transformers/template.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { Template } from '@/types/template.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import TemplateController from '@/wayfinder/actions/App/Http/Controllers/Admin/TemplateController'

export type TemplateQueryParams = QueryBuilderParams<'name' | 'isAdminOnly'>

export const templateSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(1000).nullable(),
    vmid: z.coerce.number().min(100).max(999999999),
    isAdminOnly: z.boolean(),
})

// TemplateController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute =
    TemplateController.index['/api/admin/template-groups/{template_group}/templates']
const storeRoute =
    TemplateController.store['/api/admin/template-groups/{template_group}/templates']
const updateRoute =
    TemplateController.update[
        '/api/admin/template-groups/{template_group}/templates/{template}'
    ]
const destroyRoute =
    TemplateController.destroy[
        '/api/admin/template-groups/{template_group}/templates/{template}'
    ]

export const getTemplates = async (
    templateGroupUuid: string,
    params: TemplateQueryParams
): Promise<Template[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(
        indexRoute(templateGroupUuid),
        { params: withQueryBuilderParams(params) }
    )

    return res.data.map(rawDataToTemplate)
}

export const templateQueries = {
    all: (templateGroupUuid: string | null | undefined) =>
        ['admin', 'template-groups', templateGroupUuid, 'templates'] as const,
    list: (
        templateGroupUuid: string | null | undefined,
        params: TemplateQueryParams
    ) =>
        queryOptions({
            queryKey: [
                ...templateQueries.all(templateGroupUuid),
                'list',
                params,
            ] as const,
            queryFn: () => getTemplates(templateGroupUuid!, params),
            enabled: !!templateGroupUuid,
            placeholderData: keepPreviousData,
        }),
}

export const useTemplates = (
    templateGroupUuid: string | null | undefined,
    params: TemplateQueryParams
) => useQuery(templateQueries.list(templateGroupUuid, params))

export const createTemplate = async (
    templateGroupUuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof templateSchema>
): Promise<Template> =>
    rawDataToTemplate(
        (
            await apiFetch<DataResponse<unknown>>(
                storeRoute(templateGroupUuid),
                { body: { ...rest, is_admin_only: isAdminOnly } }
            )
        ).data
    )

export const updateTemplate = async (
    templateGroupUuid: string,
    templateUuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof templateSchema>
): Promise<Template> =>
    rawDataToTemplate(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({
                    template_group: templateGroupUuid,
                    template: templateUuid,
                }),
                { body: { ...rest, is_admin_only: isAdminOnly } }
            )
        ).data
    )

export const deleteTemplate = async (
    templateGroupUuid: string,
    templateUuid: string
): Promise<void> => {
    await apiFetch(
        destroyRoute({
            template_group: templateGroupUuid,
            template: templateUuid,
        })
    )
}

export const useDeleteTemplateMutation = (templateGroupUuid: string) => {
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
                    current ? current.filter(t => t.uuid !== templateUuid) : []
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
