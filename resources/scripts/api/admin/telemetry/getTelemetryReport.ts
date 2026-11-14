import http from '@/api/http'

/**
 * The report exactly as the daily job would send it now, untouched: shown to
 * the admin as JSON, so the keys are kept as the console receives them.
 */
const getTelemetryReport = async (): Promise<Record<string, unknown>> => {
    const {
        data: { data },
    } = await http.get('/api/admin/settings/telemetry/report')

    return data
}

export default getTelemetryReport
