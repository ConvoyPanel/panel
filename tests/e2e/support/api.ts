import { APIResponse, Page } from '@playwright/test'

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

/**
 * Calls the panel's JSON API with the page's session, the way the SPA's axios
 * instance does: same cookies, the XSRF token echoed back as a header.
 */
const call = async (
    page: Page,
    method: Method,
    url: string,
    data?: unknown
): Promise<APIResponse> => {
    const cookies = await page.context().cookies()
    const xsrf = cookies.find(cookie => cookie.name === 'XSRF-TOKEN')

    return page.request.fetch(url, {
        method,
        data,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.value) } : {}),
        },
    })
}

export const api = (page: Page) => ({
    get: (url: string) => call(page, 'GET', url),
    post: (url: string, data?: unknown) => call(page, 'POST', url, data),
    put: (url: string, data?: unknown) => call(page, 'PUT', url, data),
    delete: (url: string) => call(page, 'DELETE', url),
})

/** `GET` and unwrap `data`, failing loudly on anything but 2xx. */
export const getData = async <T = any>(page: Page, url: string): Promise<T> => {
    const response = await api(page).get(url)

    if (!response.ok()) {
        throw new Error(
            `GET ${url} answered ${response.status()}: ${await response.text()}`
        )
    }

    return (await response.json()).data
}

export interface AdminUser {
    id: number
    name: string
    email: string
}

/** Looks an account up through the admin users list (`{ items, pagination }`). */
export const findUser = async (
    page: Page,
    email: string
): Promise<AdminUser> => {
    const url = `/api/admin/users?filter[email]=${encodeURIComponent(email)}`
    const response = await api(page).get(url)

    if (!response.ok()) {
        throw new Error(
            `GET ${url} answered ${response.status()}: ${await response.text()}`
        )
    }

    const { items } = await response.json()
    const [user] = Array.isArray(items) ? items : items.data

    if (!user)
        throw new Error(`No account for ${email}; did the E2eSeeder run?`)

    return user
}

/** Sets an account's admin role (`null` takes it away), the way the user form does. */
export const assignRole = async (
    page: Page,
    email: string,
    roleId: number | null
) => {
    const user = await findUser(page, email)

    return api(page).put(`/api/admin/users/${user.id}`, {
        name: user.name,
        email: user.email,
        admin_role_id: roleId,
    })
}
