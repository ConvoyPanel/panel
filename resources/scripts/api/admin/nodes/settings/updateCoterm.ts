import http from '@/api/http'

interface UpdateCotermParameters {
    isEnabled: boolean
    fqdn: string
    port: number
    isTlsEnabled: boolean
}

const updateCoterm = async (nodeId: number, { isEnabled, fqdn, port, isTlsEnabled }: UpdateCotermParameters) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/nodes/${nodeId}/settings/coterm`, {
        is_enabled: isEnabled,
        fqdn,
        port,
        is_tls_enabled: isTlsEnabled,
    })

    return {
        isEnabled: data.is_enabled as boolean,
        fqdn: data.fqdn as string,
        port: data.port as number,
        is_tls_enabled: data.port as boolean,
        token: data.token as string | null,
    }
}

export default updateCoterm
