import http from '@/api/http'

export interface TelemetrySettings {
    enabled: boolean
    /** The environment variable that turned telemetry off, or null when the admin switch decides. */
    disabledBy: 'CONVOY_TELEMETRY_ENABLED' | 'CONVOY_TELEMETRY_URL' | null
}

export const rawDataToTelemetrySettings = (data: any): TelemetrySettings => ({
    enabled: data.enabled,
    disabledBy: data.disabled_by,
})

const getTelemetrySettings = async (): Promise<TelemetrySettings> => {
    const {
        data: { data },
    } = await http.get('/api/admin/settings/telemetry')

    return rawDataToTelemetrySettings(data)
}

export default getTelemetrySettings
