import ImageUploadController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageUploadController'

import { apiFetch } from '@/lib/api'

/**
 * A disk image is handed over in pieces.
 *
 * One request was the wrong shape for a ten-gigabyte file: Cloudflare rejects a
 * body over 100 MB on most plans, and a connection that drops at 90% of a
 * single POST costs the whole transfer. So an upload is opened, appended to,
 * and finished — and the offset the server reports is what makes a dropped
 * connection cost one chunk instead of everything.
 */

const base = '/api/admin/images/uploads'

const openRoute = ImageUploadController.store[base]
const stateRoute = ImageUploadController.show[`${base}/{image_upload}`]
const appendRoute = ImageUploadController.append[`${base}/{image_upload}`]
const finalizeRoute =
    ImageUploadController.finalize[`${base}/{image_upload}/finalize`]
const cancelRoute = ImageUploadController.destroy[`${base}/{image_upload}`]

/** Where an upload has got to, and how much travels in one request. */
export interface UploadState {
    uuid: string
    offset: number
    size: number
    chunkSize: number
    fileName: string
    format: string
}

/** What the version form needs, once the file is assembled and hashed. */
export interface UploadedDisk {
    path: string
    sha256: string
    size: number
    virtualSize: number
    format: string
}

const toState = (raw: any): UploadState => ({
    uuid: raw.uuid,
    offset: raw.offset,
    size: raw.size,
    chunkSize: raw.chunk_size,
    fileName: raw.file_name,
    format: raw.format,
})

export const openImageUpload = async (file: File): Promise<UploadState> =>
    toState(
        await apiFetch<any>(openRoute(), {
            body: { file_name: file.name, size: file.size },
        })
    )

export const getImageUpload = async (uuid: string): Promise<UploadState> =>
    toState(await apiFetch<any>(stateRoute(uuid)))

/**
 * Send one chunk at `offset`.
 *
 * The body is the bytes themselves rather than a form field: a multipart POST
 * would put the chunk back through PHP's upload handling and reimpose the very
 * limits this exists to get out from under.
 */
export const appendImageChunk = async (
    uuid: string,
    chunk: Blob,
    offset: number,
    options: {
        signal?: AbortSignal
        onProgress?: (sentInChunk: number) => void
    } = {}
): Promise<UploadState> =>
    toState(
        await apiFetch<any>(appendRoute(uuid), {
            body: chunk,
            headers: {
                'Upload-Offset': String(offset),
                'Content-Type': 'application/offset+octet-stream',
            },
            signal: options.signal,
            onUploadProgress: loaded => options.onProgress?.(loaded),
        })
    )

export const finalizeImageUpload = async (
    uuid: string
): Promise<UploadedDisk> => {
    const data = await apiFetch<any>(finalizeRoute(uuid))

    return {
        path: data.path,
        sha256: data.sha256,
        size: data.size,
        virtualSize: data.virtual_size,
        format: data.format,
    }
}

export const cancelImageUpload = async (uuid: string): Promise<void> => {
    await apiFetch(cancelRoute(uuid))
}
