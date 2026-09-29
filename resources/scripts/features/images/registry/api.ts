import { RegistryCatalog, RegistryImportResult } from '@/types/image.ts'
import ImageRegistryController from '@/wayfinder/actions/App/Http/Controllers/Admin/Images/ImageRegistryController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

/**
 * A published catalogue of images, browsed and copied from.
 *
 * Read-only on the catalogue's side. Importing an entry leaves an ordinary
 * image definition behind, which is then the panel's own to edit, retire and
 * delete — nothing here subscribes to anything.
 */

const indexRoute = ImageRegistryController.index['/api/admin/images/registry']
const importRoute =
    ImageRegistryController.store['/api/admin/images/registry/imports']

export const getRegistryCatalog = async (
    refresh = false
): Promise<RegistryCatalog> =>
    (
        await apiFetch<DataResponse<RegistryCatalog>>(indexRoute(), {
            params: refresh ? { refresh: 1 } : undefined,
        })
    ).data

export const registryQueries = {
    all: () => ['admin', 'images', 'registry'] as const,
    catalog: () =>
        queryOptions({
            queryKey: registryQueries.all(),
            queryFn: () => getRegistryCatalog(),
            // The panel caches the fetch server-side too; this only stops a
            // reopened sheet from asking again in the same sitting.
            staleTime: 1000 * 60 * 5,
        }),
}

export const useRegistryCatalog = () => useQuery(registryQueries.catalog())

export const importRegistryTemplates = async (
    slugs: string[]
): Promise<RegistryImportResult[]> =>
    (
        await apiFetch<DataResponse<RegistryImportResult[]>>(importRoute(), {
            body: { templates: slugs },
        })
    ).data
