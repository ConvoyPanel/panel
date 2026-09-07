import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'
import { z } from 'zod'

import { rawDataToImageGroup } from '@/lib/transformers/image.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { ImageGroup, ImageIcon } from '@/types/image.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import ImageGroupController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageGroupController'

export type ImageGroupQueryParams = QueryBuilderParams<'name' | 'isAdminOnly'>

export const imageGroupSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(500),
    icon: z.preprocess(
        val => (val === '' ? null : val),
        z.nativeEnum(ImageIcon).nullable()
    ),
    isAdminOnly: z.boolean(),
})

// ImageGroupController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute = ImageGroupController.index['/api/admin/image-groups']
const showRoute =
    ImageGroupController.show['/api/admin/image-groups/{image_group}']
const storeRoute = ImageGroupController.store['/api/admin/image-groups']
const updateRoute =
    ImageGroupController.update['/api/admin/image-groups/{image_group}']
const destroyRoute =
    ImageGroupController.destroy['/api/admin/image-groups/{image_group}']

export const getImageGroups = async (
    params: ImageGroupQueryParams
): Promise<ImageGroup[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return res.data.map(rawDataToImageGroup)
}

export const getImageGroup = async (uuid: string): Promise<ImageGroup> =>
    rawDataToImageGroup(
        (await apiFetch<DataResponse<unknown>>(showRoute(uuid))).data
    )

export const imageGroupQueries = {
    all: () => ['admin', 'image-groups'] as const,
    lists: () => [...imageGroupQueries.all(), 'list'] as const,
    list: (params: ImageGroupQueryParams) =>
        queryOptions({
            queryKey: [...imageGroupQueries.lists(), params] as const,
            queryFn: () => getImageGroups(params),
            placeholderData: keepPreviousData,
        }),
}

export const useImageGroups = (params: ImageGroupQueryParams) =>
    useQuery(imageGroupQueries.list(params))

export const createImageGroup = async ({
    isAdminOnly,
    ...rest
}: z.infer<typeof imageGroupSchema>): Promise<ImageGroup> =>
    rawDataToImageGroup(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(), {
                body: { ...rest, is_admin_only: isAdminOnly },
            })
        ).data
    )

export const updateImageGroup = async (
    uuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof imageGroupSchema>
): Promise<ImageGroup> =>
    rawDataToImageGroup(
        (
            await apiFetch<DataResponse<unknown>>(updateRoute(uuid), {
                body: { ...rest, is_admin_only: isAdminOnly },
            })
        ).data
    )

export const deleteImageGroup = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}
