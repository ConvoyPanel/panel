import useSWR from 'swr'

import getTelemetrySettings, {
    TelemetrySettings,
} from '@/api/admin/telemetry/getTelemetrySettings'

const useTelemetrySettingsSWR = () =>
    useSWR<TelemetrySettings>('admin:settings:telemetry', getTelemetrySettings)

export default useTelemetrySettingsSWR
