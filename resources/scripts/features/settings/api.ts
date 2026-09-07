import {
    BYTES_PER_MB,
    overagePenaltyFields,
    refineOveragePenalty,
} from '@/features/bandwidth/overage-penalty.ts'
import AccountSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/AccountSettingsController'
import AnchorSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/AnchorSettingsController'
import BandwidthSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/BandwidthSettingsController'
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
 * What a non-admin may change about their own account. Three plain booleans
 * rather than a per-account policy: the panel has one role bit and no groups,
 * so there is nothing below the install to scope them to.
 */
export const accountSettingsSchema = z.object({
    allowNameChange: z.boolean(),
    allowEmailChange: z.boolean(),
    allowPasswordChange: z.boolean(),
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
            },
        })
    ).data
