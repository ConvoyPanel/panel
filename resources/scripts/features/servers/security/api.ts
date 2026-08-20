import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

/** The authorized SSH keys currently on the server (full OpenSSH public-key strings). */
export const getServerSSHKeys = async (uuid: string): Promise<string[]> => {
    const { data } = await apiFetch<DataResponse<{ sshKeys: string[] }>>(
        SettingsController.getAuthSettings(uuid)
    )

    return data.sshKeys
}

/** Replaces the server's authorized key set (the backend takes the full newline-joined list). */
export const updateServerSSHKeys = async (
    uuid: string,
    keys: string[]
): Promise<void> => {
    await apiFetch(SettingsController.updateAuthSettings(uuid), {
        body: { type: 'ssh_keys', ssh_keys: keys.join('\n') },
    })
}

/**
 * Mirrors UpdateAuthSettingsRequest's `password` rules — length, then App\Rules\Password
 * (composition) and App\Rules\USKeyboardCharacters (printable ASCII). Only the length half
 * was mirrored before, so every rejection the composition rule made came back from the
 * server as the raw `validation.password.default` key.
 *
 * Deliberately unlike the account password (utils/password.ts), which follows NIST and
 * imposes no composition rules: this one is handed to the guest OS via cloud-init, whose
 * own policy is what these rules exist to satisfy.
 */
export const passwordSchema = z
    .object({
        password: z
            .string()
            .min(8, 'Password must be at least 8 characters')
            .max(191, 'Password must be at most 191 characters')
            .regex(
                /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])/,
                'Password must contain 1 uppercase, 1 lowercase, 1 number and 1 special character'
            )
            // The guest gets this password through cloud-init and types it at a console,
            // so it has to stay on the keys a US layout actually has.
            .regex(
                /^[\x20-\x7F]*$/,
                'Password must only contain characters from the US keyboard'
            ),
        passwordConfirmation: z.string().min(1, 'Please confirm the password'),
    })
    .refine(data => data.password === data.passwordConfirmation, {
        message: "Passwords don't match",
        path: ['passwordConfirmation'],
    })

export type PasswordInput = z.infer<typeof passwordSchema>

export const updateServerPassword = async (
    uuid: string,
    password: string
): Promise<void> => {
    await apiFetch(SettingsController.updateAuthSettings(uuid), {
        body: { type: 'password', password },
    })
}

export const serverSSHKeyQueries = {
    all: (uuid: string) => ['servers', uuid, 'ssh-keys'] as const,
    detail: (uuid: string) =>
        queryOptions({
            queryKey: serverSSHKeyQueries.all(uuid),
            queryFn: () => getServerSSHKeys(uuid),
        }),
}

export const useServerSSHKeys = (uuid: string) =>
    useQuery(serverSSHKeyQueries.detail(uuid))

/** Splits a `<algorithm> <base64> [comment]` key into its algorithm and (optional) comment. */
export const describeSSHKey = (
    key: string
): { algorithm: string; comment: string | null } => {
    const [algorithm, , ...rest] = key.trim().split(/\s+/)
    return {
        algorithm: algorithm ?? 'ssh-key',
        comment: rest.length ? rest.join(' ') : null,
    }
}
