import type { AdminRole, AdminRoleInput } from '@/features/roles/types.ts'
import AdminRoleController from '@/wayfinder/actions/App/Http/Controllers/Admin/AdminRoleController'
import { queryOptions, useQuery } from '@tanstack/react-query'

import { type DataResponse, apiFetch } from '@/lib/api'

// AdminRoleController is served under both the panel (`/api/admin`) and Application
// (`/api/application`) prefixes, so Wayfinder emits URI-keyed dictionaries — reference the admin
// route explicitly.
const indexRoute = AdminRoleController.index['/api/admin/admin-roles']
const storeRoute = AdminRoleController.store['/api/admin/admin-roles']
const updateRoute =
    AdminRoleController.update['/api/admin/admin-roles/{admin_role}']
const destroyRoute =
    AdminRoleController.destroy['/api/admin/admin-roles/{admin_role}']

const getRoles = async (): Promise<AdminRole[]> =>
    (await apiFetch<DataResponse<AdminRole[]>>(indexRoute())).data

export const roleQueries = {
    all: () => ['admin', 'roles'] as const,
    list: () =>
        queryOptions({
            queryKey: roleQueries.all(),
            queryFn: getRoles,
        }),
}

export const useAdminRoles = () => useQuery(roleQueries.list())

const payload = ({ name, description, permissions }: AdminRoleInput) => ({
    name,
    description: description === '' ? null : description,
    permissions,
})

export const createAdminRole = async (
    input: AdminRoleInput
): Promise<AdminRole> =>
    (
        await apiFetch<DataResponse<AdminRole>>(storeRoute(), {
            body: payload(input),
        })
    ).data

export const updateAdminRole = async (
    role: string,
    input: AdminRoleInput
): Promise<AdminRole> =>
    (
        await apiFetch<DataResponse<AdminRole>>(updateRoute(role), {
            body: payload(input),
        })
    ).data

export const deleteAdminRole = async (role: string): Promise<void> => {
    await apiFetch(destroyRoute(role))
}
