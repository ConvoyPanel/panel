import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { TemplateGroup, TemplateIcon } from '@/types/template-group.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import TemplateGroupController from '@/wayfinder/actions/App/Http/Controllers/Admin/TemplateGroupController'

export type TemplateGroupQueryParams = QueryBuilderParams<'name' | 'isAdminOnly'>

export const templateGroupSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(500),
    icon: z.preprocess(
        val => (val === '' ? null : val),
        z.nativeEnum(TemplateIcon).nullable()
    ),
    isAdminOnly: z.boolean(),
})

// TemplateGroupController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute = TemplateGroupController.index['/api/admin/template-groups']
const showRoute =
    TemplateGroupController.show['/api/admin/template-groups/{template_group}']
const storeRoute = TemplateGroupController.store['/api/admin/template-groups']
const updateRoute =
    TemplateGroupController.update['/api/admin/template-groups/{template_group}']
const destroyRoute =
    TemplateGroupController.destroy['/api/admin/template-groups/{template_group}']

export const getTemplateGroups = async (
    params: TemplateGroupQueryParams
): Promise<TemplateGroup[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return res.data.map(rawDataToTemplateGroup)
}

export const getTemplateGroup = async (
    uuid: string
): Promise<TemplateGroup> =>
    rawDataToTemplateGroup(
        (await apiFetch<DataResponse<unknown>>(showRoute(uuid))).data
    )

export const templateGroupQueries = {
    all: () => ['admin', 'template-groups'] as const,
    lists: () => [...templateGroupQueries.all(), 'list'] as const,
    list: (params: TemplateGroupQueryParams) =>
        queryOptions({
            queryKey: [...templateGroupQueries.lists(), params] as const,
            queryFn: () => getTemplateGroups(params),
            placeholderData: keepPreviousData,
        }),
}

export const useTemplateGroups = (params: TemplateGroupQueryParams) =>
    useQuery(templateGroupQueries.list(params))

export const createTemplateGroup = async ({
    isAdminOnly,
    ...rest
}: z.infer<typeof templateGroupSchema>): Promise<TemplateGroup> =>
    rawDataToTemplateGroup(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(), {
                body: { ...rest, is_admin_only: isAdminOnly },
            })
        ).data
    )

export const updateTemplateGroup = async (
    uuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof templateGroupSchema>
): Promise<TemplateGroup> =>
    rawDataToTemplateGroup(
        (
            await apiFetch<DataResponse<unknown>>(updateRoute(uuid), {
                body: { ...rest, is_admin_only: isAdminOnly },
            })
        ).data
    )

export const deleteTemplateGroup = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}
