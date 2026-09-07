import ISOController from '@/wayfinder/actions/App/Http/Controllers/Admin/ISOs/ISOController'
import ISOUploadController from '@/wayfinder/actions/App/Http/Controllers/Admin/ISOs/ISOUploadController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'
import axios from '@/lib/axios'

export interface ISO {
    uuid: string
    name: string
    /** What this ISO is called on every node that fetches it. */
    fileName: string
    /** Set when the operator hosts it; null when the panel does. */
    url: string | null
    isHosted: boolean
    sha256: string | null
    size: number | null
    hidden: boolean
    createdAt: string
}

/**
 * A source, and only one of them: a link the operator hosts, or a file uploaded
 * to the panel. Both answer the same question, so allowing both would leave a
 * question about which one a node was handed.
 */
export const isoSchema = z
    .object({
        name: z.string().min(1).max(40),
        fileName: z
            .string()
            .min(1)
            .max(191)
            .endsWith('.iso', 'The file name must end in .iso'),
        url: z.string().url().nullable(),
        path: z.string().nullable(),
        sha256: z
            .string()
            .regex(/^[a-f0-9]{64}$/i)
            .nullable(),
        size: z.coerce.number().min(0).nullable(),
        hidden: z.boolean(),
    })
    .refine(iso => !!iso.url !== !!iso.path, {
        message: 'Give the ISO a URL or upload a file, not both.',
        path: ['url'],
    })

const indexRoute = ISOController.index['/api/admin/isos']
const storeRoute = ISOController.store['/api/admin/isos']
const updateRoute = ISOController.update['/api/admin/isos/{iso}']
const destroyRoute = ISOController.destroy['/api/admin/isos/{iso}']
const queryLinkRoute =
    ISOController.queryLink['/api/admin/isos/query-remote-file']
const uploadRoute = ISOUploadController.store['/api/admin/isos/uploads']

interface Paginated {
    items: ISO[]
    pagination: App.Data.PaginationMeta
}

export const getISOs = async (search?: string): Promise<Paginated> =>
    apiFetch<Paginated>(indexRoute(), {
        params: search ? { 'filter[name]': search } : undefined,
    })

export const isoQueries = {
    all: () => ['admin', 'isos'] as const,
    list: (search?: string) =>
        queryOptions({
            queryKey: [...isoQueries.all(), search ?? ''] as const,
            queryFn: () => getISOs(search),
            placeholderData: keepPreviousData,
        }),
}

export const useISOs = (search?: string) => useQuery(isoQueries.list(search))

const toBody = ({ fileName, ...rest }: z.infer<typeof isoSchema>) => ({
    ...rest,
    file_name: fileName,
})

export const createISO = async (
    values: z.infer<typeof isoSchema>
): Promise<ISO> =>
    (await apiFetch<DataResponse<ISO>>(storeRoute(), { body: toBody(values) }))
        .data

/** Only the label and its visibility; the source is fixed once nodes have it. */
export const updateISO = async (
    uuid: string,
    values: { name: string; hidden: boolean }
): Promise<ISO> =>
    (await apiFetch<DataResponse<ISO>>(updateRoute(uuid), { body: values }))
        .data

export const deleteISO = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}

/** Asks Proxmox what a URL points at, so the form can fill its own blanks. */
export const queryRemoteFile = async (
    link: string
): Promise<{ fileName: string; size: number }> => {
    const data = await apiFetch<any>(queryLinkRoute(), { params: { link } })

    return {
        fileName: data.data?.fileName ?? data.fileName,
        size: data.data?.size ?? data.size,
    }
}

export interface UploadedISO {
    path: string
    sha256: string
    size: number
    fileName: string
}

export const uploadISO = async (
    file: File,
    onProgress?: (fraction: number) => void
): Promise<UploadedISO> => {
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
        fileName: data.file_name,
    }
}
