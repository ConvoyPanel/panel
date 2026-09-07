import { HardwareSchema } from '@/types/image.ts'
import ImageSchemaController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageSchemaController'
import ImageUploadController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageUploadController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch } from '@/lib/api'
import axios from '@/lib/axios'

const schemaRoute = ImageSchemaController['/api/admin/images/schema']
const uploadRoute = ImageUploadController.store['/api/admin/images/uploads']

/**
 * Proxmox's own parameter definitions, so the hardware form builds itself.
 *
 * Fetched per node when one is in scope: PVE's enums and defaults move between
 * releases, and the only version whose opinion matters is the one the create
 * call will land on.
 */
export const getHardwareSchema = async (
    nodeId?: number | null
): Promise<HardwareSchema> => {
    const raw = await apiFetch<any>(schemaRoute(), {
        params: nodeId ? { node_id: nodeId } : undefined,
    })

    return {
        nodeId: raw.node_id ?? null,
        parameters: raw.parameters ?? {},
        metaKeys: raw.meta_keys ?? [],
        defaults: raw.defaults ?? {},
    }
}

export const hardwareSchemaQueries = {
    all: () => ['admin', 'images', 'schema'] as const,
    forNode: (nodeId?: number | null) =>
        queryOptions({
            queryKey: [...hardwareSchemaQueries.all(), nodeId ?? null] as const,
            queryFn: () => getHardwareSchema(nodeId),
            // It only changes when a node is upgraded.
            staleTime: 1000 * 60 * 60,
        }),
}

export const useHardwareSchema = (nodeId?: number | null) =>
    useQuery(hardwareSchemaQueries.forNode(nodeId))

export interface UploadedDisk {
    path: string
    sha256: string
    size: number
    virtualSize: number
    format: string
}

/**
 * Hands a disk image to the panel to host.
 *
 * Comes back described rather than merely stored: the hash and the provisioned
 * size are properties of the file, and neither should be typed by hand.
 */
export const uploadImageDisk = async (
    file: File,
    onProgress?: (fraction: number) => void
): Promise<UploadedDisk> => {
    const body = new FormData()
    body.append('file', file)

    const { data } = await axios.request<any>({
        url: uploadRoute().url,
        method: uploadRoute().method,
        data: body,
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: event => {
            if (onProgress && event.total) {
                onProgress(event.loaded / event.total)
            }
        },
    })

    return {
        path: data.path,
        sha256: data.sha256,
        size: data.size,
        virtualSize: data.virtual_size,
        format: data.format,
    }
}
