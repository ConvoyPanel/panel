import {
    BYTES_PER_MB,
    overagePenaltyFields,
    refineOveragePenalty,
} from '@/features/bandwidth/overage-penalty.ts'
import AccountSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/AccountSettingsController'
import AnchorSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/AnchorSettingsController'
import BandwidthSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/BandwidthSettingsController'
import MailSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/MailSettingsController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

export type BandwidthSettings = App.Data.Admin.Settings.BandwidthSettingsData

// BandwidthSettingsController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const showRoute =
    BandwidthSettingsController.show['/api/admin/settings/bandwidth']
const updateRoute =
    BandwidthSettingsController.update['/api/admin/settings/bandwidth']

/**
 * The global tier is the bottom of the cascade, so there's no "inherit" mode
 * here — only the action and (for a throttle) its rate.
 */
export const bandwidthSettingsSchema = refineOveragePenalty(
    z.object({
        overagePenaltyAction: overagePenaltyFields.overagePenaltyAction,
        overagePenaltyRate: overagePenaltyFields.overagePenaltyRate,
        // `refineOveragePenalty` only requires a rate in "custom" mode; the
        // global tier is always concrete, so pin the mode to satisfy it.
        overagePenaltyMode: z.literal('custom').default('custom'),
    })
)

const getBandwidthSettings = async (): Promise<BandwidthSettings> =>
    (await apiFetch<DataResponse<BandwidthSettings>>(showRoute())).data

export const settingsQueries = {
    all: () => ['admin', 'settings'] as const,
    bandwidth: () =>
        queryOptions({
            queryKey: [...settingsQueries.all(), 'bandwidth'] as const,
            queryFn: getBandwidthSettings,
        }),
}

export const useBandwidthSettings = () => useQuery(settingsQueries.bandwidth())

export const updateBandwidthSettings = async (
    payload: z.infer<typeof bandwidthSettingsSchema>
): Promise<BandwidthSettings> => {
    const res = await apiFetch<DataResponse<BandwidthSettings>>(updateRoute(), {
        body: {
            overage_penalty:
                payload.overagePenaltyAction === 'disconnect'
                    ? { action: 'disconnect' }
                    : {
                          action: 'throttle',
                          rate: Math.round(
                              Number(payload.overagePenaltyRate) * BYTES_PER_MB
                          ),
                      },
        },
    })

    return res.data
}

// --- Anchor -----------------------------------------------------------------

export type AnchorSettings = App.Data.Admin.Settings.AnchorSettingsData

const anchorShowRoute =
    AnchorSettingsController.show['/api/admin/settings/anchor']
const anchorUpdateRoute =
    AnchorSettingsController.update['/api/admin/settings/anchor']

/**
 * Blank is meaningful here, unlike the bandwidth tier: it means "fall through
 * to the panel's own URL", which is correct whenever that address resolves.
 */
export const anchorSettingsSchema = z.object({
    panelUrl: z.union([z.url(), z.literal('')]),
})

const getAnchorSettings = async (): Promise<AnchorSettings> =>
    (await apiFetch<DataResponse<AnchorSettings>>(anchorShowRoute())).data

export const anchorSettingsQuery = () =>
    queryOptions({
        queryKey: [...settingsQueries.all(), 'anchor'] as const,
        queryFn: getAnchorSettings,
    })

export const useAnchorSettings = () => useQuery(anchorSettingsQuery())

export const updateAnchorSettings = async (
    payload: z.infer<typeof anchorSettingsSchema>
): Promise<AnchorSettings> =>
    (
        await apiFetch<DataResponse<AnchorSettings>>(anchorUpdateRoute(), {
            body: {
                panel_url: payload.panelUrl === '' ? null : payload.panelUrl,
            },
        })
    ).data

// --- Accounts ---------------------------------------------------------------

export type AccountSettings = App.Data.Admin.Settings.AccountSettingsData

const accountShowRoute =
    AccountSettingsController.show['/api/admin/settings/account']
const accountUpdateRoute =
    AccountSettingsController.update['/api/admin/settings/account']

/**
 * What a non-admin may change about their own account. Four plain booleans
 * rather than a per-account policy: the panel has one role bit and no groups,
 * so there is nothing below the install to scope them to.
 */
export const accountSettingsSchema = z.object({
    allowNameChange: z.boolean(),
    allowEmailChange: z.boolean(),
    allowPasswordChange: z.boolean(),
    allowAvatarChange: z.boolean(),
})

const getAccountSettings = async (): Promise<AccountSettings> =>
    (await apiFetch<DataResponse<AccountSettings>>(accountShowRoute())).data

export const accountSettingsQuery = () =>
    queryOptions({
        queryKey: [...settingsQueries.all(), 'account'] as const,
        queryFn: getAccountSettings,
    })

export const useAccountSettings = () => useQuery(accountSettingsQuery())

export const updateAccountSettings = async (
    payload: z.infer<typeof accountSettingsSchema>
): Promise<AccountSettings> =>
    (
        await apiFetch<DataResponse<AccountSettings>>(accountUpdateRoute(), {
            body: {
                allow_name_change: payload.allowNameChange,
                allow_email_change: payload.allowEmailChange,
                allow_password_change: payload.allowPasswordChange,
                allow_avatar_change: payload.allowAvatarChange,
            },
        })
    ).data

// --- Mail -------------------------------------------------------------------

export type MailSettings = App.Data.Admin.Settings.MailSettingsData
export type MailEncryption = App.Enums.Mail.MailEncryption

const mailShowRoute = MailSettingsController.show['/api/admin/settings/mail']
const mailUpdateRoute =
    MailSettingsController.update['/api/admin/settings/mail']
const mailTestRoute =
    MailSettingsController.test['/api/admin/settings/mail/test']

/**
 * Blank host means "not set here", handing delivery back to MAIL_* in the
 * environment — so the rest of the form is only required once a host is given.
 * The same conditional shape the server validates with `required_with:host`.
 */
export const mailSettingsSchema = z
    .object({
        host: z.string().trim(),
        port: z.coerce.number().int().min(1).max(65535),
        username: z.string().trim(),
        // Never populated from the server: the API is write-only on this field.
        // An untouched field submits nothing and the stored secret survives.
        password: z.string(),
        encryption: z.enum(['tls', 'ssl', 'none']),
        fromAddress: z.string().trim(),
        fromName: z.string().trim(),
    })
    .superRefine((value, ctx) => {
        if (value.host === '') return

        if (!z.email().safeParse(value.fromAddress).success) {
            ctx.addIssue({
                code: 'custom',
                path: ['fromAddress'],
                message: 'Enter the address mail should be sent from.',
            })
        }

        if (value.fromName === '') {
            ctx.addIssue({
                code: 'custom',
                path: ['fromName'],
                message: 'Enter the name mail should be sent from.',
            })
        }
    })

/**
 * The shape the *form* holds, not the parsed result: `port` arrives from a number
 * input as a string, so the schema coerces and input/output diverge. Typing the
 * form on the input side is the same thing the server build and disk-resize forms
 * do for their coerced fields.
 */
export type MailSettingsForm = z.input<typeof mailSettingsSchema>

/**
 * `includePassword` is the form telling us the field was actually edited. Sending
 * the key at all is what distinguishes "keep the stored secret" (omit) from
 * "clear it" (send empty), so it cannot be inferred from the value.
 */
const mailBody = (payload: MailSettingsForm, includePassword: boolean) => ({
    host: payload.host,
    port: Number(payload.port),
    username: payload.username,
    encryption: payload.encryption,
    from_address: payload.fromAddress,
    from_name: payload.fromName,
    ...(includePassword ? { password: payload.password } : {}),
})

const getMailSettings = async (): Promise<MailSettings> =>
    (await apiFetch<DataResponse<MailSettings>>(mailShowRoute())).data

export const mailSettingsQuery = () =>
    queryOptions({
        queryKey: [...settingsQueries.all(), 'mail'] as const,
        queryFn: getMailSettings,
    })

export const useMailSettings = () => useQuery(mailSettingsQuery())

export const updateMailSettings = async ({
    payload,
    includePassword,
}: {
    payload: MailSettingsForm
    includePassword: boolean
}): Promise<MailSettings> =>
    (
        await apiFetch<DataResponse<MailSettings>>(mailUpdateRoute(), {
            body: mailBody(payload, includePassword),
        })
    ).data

/**
 * Sends through the submitted form rather than what is stored, so credentials can
 * be proven before they are committed. Resolves with the address that received it.
 */
export const testMailSettings = async ({
    payload,
    includePassword,
    recipient,
}: {
    payload: MailSettingsForm
    includePassword: boolean
    recipient?: string
}): Promise<{ recipient: string }> =>
    (
        await apiFetch<DataResponse<{ recipient: string }>>(mailTestRoute(), {
            body: {
                ...mailBody(payload, includePassword),
                ...(recipient ? { recipient } : {}),
            },
        })
    ).data
