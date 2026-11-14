import {
    TelemetrySettings,
    rawDataToTelemetrySettings,
} from '@/api/admin/telemetry/getTelemetrySettings'
import http from '@/api/http'

const updateTelemetrySettings = async (
    enabled: boolean
): Promise<TelemetrySettings> => {
    const {
        data: { data },
    } = await http.put('/api/admin/settings/telemetry', { enabled })

    return rawDataToTelemetrySettings(data)
}

export default updateTelemetrySettings
