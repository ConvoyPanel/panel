import { ImageDefinition } from '@/types/image.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'
import ImageDefinitionController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageDefinitionController'
import {
    keepPreviousData,
    queryOptions,
    useMutation,
    useQuery,
    useQueryClient,
} from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'
import { rawDataToImageDefinition } from '@/lib/transformers/image.ts'

import { toast } from '@/components/ui/Toast'

export type ImageDefinitionQueryParams = QueryBuilderParams<
    'name' | 'isAdminOnly'
>

export const imageDefinitionSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(1000).nullable(),
    isAdminOnly: z.boolean(),
    ostype: z.string().min(1).max(20),
    /**
     * Only what the admin overrode. A key left out inherits the OS default, so
     * an empty object is a complete, valid answer rather than a blank form.
     */
    hardware: z.record(z.string(), z.unknown()).optional(),
    minimumCores: z.coerce.number().min(1).nullable(),
    minimumMemory: z.coerce.number().min(1).nullable(),
})

const indexRoute =
    ImageDefinitionController.index[
        '/api/admin/image-groups/{image_group}/images'
    ]
const storeRoute =
    ImageDefinitionController.store[
        '/api/admin/image-groups/{image_group}/images'
    ]
const updateRoute =
    ImageDefinitionController.update[
        '/api/admin/image-groups/{image_group}/images/{image_definition}'
    ]
const destroyRoute =
    ImageDefinitionController.destroy[
        '/api/admin/image-groups/{image_group}/images/{image_definition}'
    ]

const toBody = ({
    isAdminOnly,
    minimumCores,
    minimumMemory,
    ...rest
}: z.infer<typeof imageDefinitionSchema>) => ({
    ...rest,
    is_admin_only: isAdminOnly,
    minimum_cores: minimumCores,
    minimum_memory: minimumMemory,
})

export const getImageDefinitions = async (
    imageGroupUuid: string,
    params: ImageDefinitionQueryParams
): Promise<ImageDefinition[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(
        indexRoute(imageGroupUuid),
        { params: withQueryBuilderParams(params) }
    )

    return res.data.map(rawDataToImageDefinition)
}

export const imageDefinitionQueries = {
    all: (imageGroupUuid: string | null | undefined) =>
        ['admin', 'image-groups', imageGroupUuid, 'images'] as const,
    list: (
        imageGroupUuid: string | null | undefined,
        params: ImageDefinitionQueryParams
    ) =>
        queryOptions({
            queryKey: [
                ...imageDefinitionQueries.all(imageGroupUuid),
                'list',
                params,
            ] as const,
            queryFn: () => getImageDefinitions(imageGroupUuid!, params),
            enabled: !!imageGroupUuid,
            placeholderData: keepPreviousData,
        }),
}

export const useImageDefinitions = (
    imageGroupUuid: string | null | undefined,
    params: ImageDefinitionQueryParams
) => useQuery(imageDefinitionQueries.list(imageGroupUuid, params))

export const createImageDefinition = async (
    imageGroupUuid: string,
    values: z.infer<typeof imageDefinitionSchema>
): Promise<ImageDefinition> =>
    rawDataToImageDefinition(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(imageGroupUuid), {
                body: toBody(values),
            })
        ).data
    )

export const updateImageDefinition = async (
    imageGroupUuid: string,
    imageUuid: string,
    values: z.infer<typeof imageDefinitionSchema>
): Promise<ImageDefinition> =>
    rawDataToImageDefinition(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({
                    image_group: imageGroupUuid,
                    image_definition: imageUuid,
                }),
                { body: toBody(values) }
            )
        ).data
    )

export const deleteImageDefinition = async (
    imageGroupUuid: string,
    imageUuid: string
): Promise<void> => {
    await apiFetch(
        destroyRoute({
            image_group: imageGroupUuid,
            image_definition: imageUuid,
        })
    )
}

export const useDeleteImageDefinitionMutation = (imageGroupUuid: string) => {
    const queryClient = useQueryClient()

    const mutation = useMutation({
        mutationFn: async (imageUuid: string) => {
            await deleteImageDefinition(imageGroupUuid, imageUuid)
            return imageUuid
        },
        onSuccess: imageUuid => {
            queryClient.setQueryData<ImageDefinition[]>(
                imageDefinitionQueries.list(imageGroupUuid, {}).queryKey,
                current =>
                    current ? current.filter(i => i.uuid !== imageUuid) : []
            )
            toast.add({ title: 'Image deleted successfully', type: 'success' })
        },
        onError: () => {
            toast.add({ title: 'Failed to delete image', type: 'error' })
        },
    })

    return {
        trigger: (imageUuid: string) => mutation.mutateAsync(imageUuid),
        isMutating: mutation.isPending,
    }
}
