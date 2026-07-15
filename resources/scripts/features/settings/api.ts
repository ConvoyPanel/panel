import BandwidthSettingsController from '@/wayfinder/actions/App/Http/Controllers/Admin/Settings/BandwidthSettingsController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import {
    BYTES_PER_MB,
    overagePenaltyFields,
    refineOveragePenalty,
} from '@/features/bandwidth/overage-penalty.ts'

import { type DataResponse, apiFetch } from '@/lib/api'

export type BandwidthSettings = App.Data.Admin.Settings.BandwidthSettingsData

// BandwidthSettingsController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const showRoute = BandwidthSettingsController.show['/api/admin/settings/bandwidth']
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
