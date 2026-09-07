import { ImageDiskRole, ImageVersion } from '@/types/image.ts'
import ImageVersionController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageVersionController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'
import { rawDataToImageVersion } from '@/lib/transformers/image.ts'

/**
 * A disk is described by a source and a hash, and the source is either a link
 * the operator hosts or a file they uploaded. Never both — a node receives one
 * URL either way, so allowing both would only leave a question about which won.
 */
export const imageDiskSchema = z
    .object({
        slot: z.string().regex(/^(?:scsi|ide|sata|virtio|efidisk)\d+$/),
        role: z.nativeEnum(ImageDiskRole),
        url: z.string().url().nullable(),
        path: z.string().nullable(),
        sha256: z.string().regex(/^[a-f0-9]{64}$/i),
        size: z.coerce.number().min(1),
        virtualSize: z.coerce.number().min(1),
        format: z.enum(['qcow2', 'raw']).default('qcow2'),
    })
    .refine(disk => !!disk.url !== !!disk.path, {
        message: 'Give the disk a URL or upload a file, not both.',
        path: ['url'],
    })

export const imageVersionSchema = z.object({
    version: z.string().regex(/^\d+\.\d+\.\d+$/, 'Use major.minor.patch'),
    isActive: z.boolean().default(true),
    disks: z
        .array(imageDiskSchema)
        .min(1)
        .refine(
            disks =>
                disks.filter(d => d.role === ImageDiskRole.SYSTEM).length === 1,
            { message: 'A version needs exactly one system disk.' }
        ),
})

const base = '/api/admin/image-groups/{image_group}/images/{image_definition}'

const indexRoute = ImageVersionController.index[`${base}/versions`]
const storeRoute = ImageVersionController.store[`${base}/versions`]
const updateRoute =
    ImageVersionController.update[`${base}/versions/{image_version}`]
const destroyRoute =
    ImageVersionController.destroy[`${base}/versions/{image_version}`]

const params = (groupUuid: string, imageUuid: string) => ({
    image_group: groupUuid,
    image_definition: imageUuid,
})

const toBody = ({
    isActive,
    disks,
    ...rest
}: z.infer<typeof imageVersionSchema>) => ({
    ...rest,
    is_active: isActive,
    disks: disks.map(({ virtualSize, ...disk }) => ({
        ...disk,
        virtual_size: virtualSize,
    })),
})

export const getImageVersions = async (
    groupUuid: string,
    imageUuid: string
): Promise<ImageVersion[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(
        indexRoute(params(groupUuid, imageUuid))
    )

    return res.data.map(rawDataToImageVersion)
}

export const imageVersionQueries = {
    all: (
        groupUuid: string | null | undefined,
        imageUuid: string | null | undefined
    ) =>
        [
            'admin',
            'image-groups',
            groupUuid,
            'images',
            imageUuid,
            'versions',
        ] as const,
    list: (
        groupUuid: string | null | undefined,
        imageUuid: string | null | undefined
    ) =>
        queryOptions({
            queryKey: imageVersionQueries.all(groupUuid, imageUuid),
            queryFn: () => getImageVersions(groupUuid!, imageUuid!),
            enabled: !!groupUuid && !!imageUuid,
        }),
}

export const useImageVersions = (
    groupUuid: string | null | undefined,
    imageUuid: string | null | undefined
) => useQuery(imageVersionQueries.list(groupUuid, imageUuid))

export const createImageVersion = async (
    groupUuid: string,
    imageUuid: string,
    values: z.infer<typeof imageVersionSchema>
): Promise<ImageVersion> =>
    rawDataToImageVersion(
        (
            await apiFetch<DataResponse<unknown>>(
                storeRoute(params(groupUuid, imageUuid)),
                { body: toBody(values) }
            )
        ).data
    )

/**
 * Only the retired flag is editable. A version's disks are what a server was
 * built from, so rewriting them would change the answer to "where did this
 * machine come from" for every server already pointing at it.
 */
export const setImageVersionActive = async (
    groupUuid: string,
    imageUuid: string,
    versionUuid: string,
    isActive: boolean
): Promise<ImageVersion> =>
    rawDataToImageVersion(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({
                    ...params(groupUuid, imageUuid),
                    image_version: versionUuid,
                }),
                { body: { is_active: isActive } }
            )
        ).data
    )

export const deleteImageVersion = async (
    groupUuid: string,
    imageUuid: string,
    versionUuid: string
): Promise<void> => {
    await apiFetch(
        destroyRoute({
            ...params(groupUuid, imageUuid),
            image_version: versionUuid,
        })
    )
}
