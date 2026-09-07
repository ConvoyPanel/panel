import { rawDataToAuthenticatedUser } from '@/features/auth/transforms.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import AvatarController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/AvatarController'
import ProfileController from '@/wayfinder/actions/App/Http/Controllers/Client/Account/ProfileController'
import axios from 'axios'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

export const profileSchema = z.object({
    name: z.string().min(1, 'A display name is required').max(191),
})

export const emailSchema = z.object({
    email: z.string().email('Enter a valid email address').max(191),
})

/** Kept in step with the `mimes` rule the upload endpoint validates against. */
export const AVATAR_ACCEPT = 'image/jpeg,image/png,image/webp,image/gif'

/** The 10MB ceiling from UpdateAvatarRequest, checked before we send the bytes. */
export const AVATAR_MAX_BYTES = 10 * 1024 * 1024

export const updateProfile = async (
    payload: z.infer<typeof profileSchema>
): Promise<AuthenticatedUser> => {
    const { data } = await apiFetch<DataResponse<any>>(ProfileController.update(), {
        body: payload,
    })

    return rawDataToAuthenticatedUser(data)
}

export const updateEmail = async (
    payload: z.infer<typeof emailSchema>
): Promise<AuthenticatedUser> => {
    const { data } = await apiFetch<DataResponse<any>>(
        ProfileController.updateEmail(),
        { body: payload }
    )

    return rawDataToAuthenticatedUser(data)
}

/**
 * Sent as multipart rather than through `apiFetch`, which serialises its body
 * as JSON. The panel keeps only the re-encoded result, so the response is the
 * account as it now stands rather than anything about the file that was sent.
 */
export const uploadAvatar = async (file: File): Promise<AuthenticatedUser> => {
    const body = new FormData()
    body.append('avatar', file)

    const route = AvatarController.store()
    const { data } = await axios.request<DataResponse<any>>({
        url: route.url,
        method: route.method,
        data: body,
        headers: { 'Content-Type': 'multipart/form-data' },
    })

    return rawDataToAuthenticatedUser(data.data)
}

export const removeAvatar = async (): Promise<AuthenticatedUser> => {
    const { data } = await apiFetch<DataResponse<any>>(AvatarController.destroy())

    return rawDataToAuthenticatedUser(data)
}
