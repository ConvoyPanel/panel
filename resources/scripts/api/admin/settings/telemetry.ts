import useSWR from 'swr'

import http from '@/api/http'

export interface TelemetrySettings {
    enabled: boolean
    /** The environment variable that keeps telemetry off, if one does. */
    disabledBy: string | null
    /** When the report goes out each day, in UTC. */
    reportTime: string | null
    reportedAt: Date | null
}

const rawDataToTelemetrySettings = (data: any): TelemetrySettings => ({
    enabled: data.enabled,
    disabledBy: data.disabled_by,
    reportTime: data.report_time,
    reportedAt: data.reported_at ? new Date(data.reported_at) : null,
})

export const getTelemetrySettings = async (): Promise<TelemetrySettings> => {
    const { data } = await http.get('/api/admin/settings/telemetry')

    return rawDataToTelemetrySettings(data.data)
}

export const updateTelemetrySettings = async (
    enabled: boolean
): Promise<TelemetrySettings> => {
    const { data } = await http.put('/api/admin/settings/telemetry', {
        enabled,
    })

    return rawDataToTelemetrySettings(data.data)
}

/** The report exactly as the next one would be sent. */
export const getTelemetryReport = async (): Promise<unknown> => {
    const { data } = await http.get('/api/admin/settings/telemetry/report')

    return data.data
}

export const dismissTelemetryNotice = () =>
    http.post('/api/admin/settings/telemetry/notice/dismiss')

export const useTelemetrySettingsSWR = () =>
    useSWR<TelemetrySettings>('admin:settings:telemetry', getTelemetrySettings)
