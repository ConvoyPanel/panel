import type { Provisioner } from '@/features/provisioners/types.ts'
import ProvisionerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ProvisionerController'
import { queryOptions, useMutation, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

// ProvisionerController is served under both /api/admin and /api/application,
// so Wayfinder emits URI-keyed dictionaries; the panel uses the admin URIs.
const indexRoute = ProvisionerController.index['/api/admin/provisioners']
const storeRoute = ProvisionerController.store['/api/admin/provisioners']
const updateRoute =
    ProvisionerController.update['/api/admin/provisioners/{provisioner}']
const destroyRoute =
    ProvisionerController.destroy['/api/admin/provisioners/{provisioner}']

/** Mirrors Provisioner::FIELD_KEY_PATTERN: the key becomes an env var name. */
export const FIELD_KEY_PATTERN = /^[a-z][a-z0-9_]{0,31}$/

export const provisionerFieldSchema = z.object({
    key: z
        .string()
        .regex(
            FIELD_KEY_PATTERN,
            'Lowercase letters, digits and underscores, starting with a letter'
        ),
    label: z.string().min(1).max(40),
    required: z.boolean(),
})

export const provisionerSchema = z
    .object({
        name: z.string().min(1).max(40),
        description: z.string().max(191),
        script: z.string().min(1).max(65536),
        fields: z.array(provisionerFieldSchema).max(20),
    })
    .superRefine((data, ctx) => {
        const seen = new Set<string>()

        data.fields.forEach((field, index) => {
            if (seen.has(field.key)) {
                ctx.addIssue({
                    code: 'custom',
                    message: 'Two fields have the same key',
                    path: ['fields', index, 'key'],
                })
            }
            seen.add(field.key)
        })
    })

export type ProvisionerInput = z.infer<typeof provisionerSchema>

const rawDataToProvisioner = (
    data: App.Data.Provisioner.ProvisionerData
): Provisioner => ({
    uuid: data.uuid,
    name: data.name,
    description: data.description,
    script: data.script,
    fields: data.fields,
    imageCount: data.imageCount,
    createdAt: new Date(data.createdAt),
    updatedAt: new Date(data.updatedAt),
})

const toBody = ({ description, ...rest }: ProvisionerInput) => ({
    ...rest,
    description: description.trim() === '' ? null : description,
})

export const getProvisioners = async (): Promise<Provisioner[]> => {
    const { data } =
        await apiFetch<DataResponse<App.Data.Provisioner.ProvisionerData[]>>(
            indexRoute()
        )

    return data.map(rawDataToProvisioner)
}

export const createProvisioner = async (
    input: ProvisionerInput
): Promise<Provisioner> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.Provisioner.ProvisionerData>
    >(storeRoute(), { body: toBody(input) })

    return rawDataToProvisioner(data)
}

export const updateProvisioner = async (
    uuid: string,
    input: ProvisionerInput
): Promise<Provisioner> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.Provisioner.ProvisionerData>
    >(updateRoute(uuid), { body: toBody(input) })

    return rawDataToProvisioner(data)
}

export const deleteProvisioner = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}

export const provisionerQueries = {
    all: () => ['admin', 'provisioners'] as const,
    list: () =>
        queryOptions({
            queryKey: [...provisionerQueries.all(), 'list'] as const,
            queryFn: getProvisioners,
        }),
}

export const useProvisioners = () => useQuery(provisionerQueries.list())

export const useCreateProvisionerMutation = () =>
    useMutation({ mutationFn: createProvisioner })

export const useUpdateProvisionerMutation = (uuid: string) =>
    useMutation({
        mutationFn: (input: ProvisionerInput) => updateProvisioner(uuid, input),
    })

export const useDeleteProvisionerMutation = () =>
    useMutation({ mutationFn: deleteProvisioner })

/** The env var a field reaches the script as. */
export const fieldEnvName = (key: string): string =>
    `CONVOY_FIELD_${key.toUpperCase()}`

/**
 * Where a 422 on a server's field values goes in the form.
 *
 * Values live under `provisionerFields.<key>`, and the key is snake_case on
 * purpose: the default mapping would camelCase `provisioner_fields.admin_email`
 * into an input that does not exist.
 */
export const provisionerFieldErrorMapping = (
    error: unknown
): Record<string, string> => {
    const errors =
        (
            error as {
                response?: { data?: { errors?: Record<string, unknown> } }
            }
        )?.response?.data?.errors ?? {}
    const prefix = 'provisioner_fields.'

    return Object.fromEntries(
        Object.keys(errors)
            .filter(key => key.startsWith(prefix))
            .map(key => [key, `provisionerFields.${key.slice(prefix.length)}`])
    )
}
