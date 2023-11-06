import http from '@/api/http'

export type ConsoleType = 'novnc' | 'xtermjs'

interface ConsoleCredentialsWithoutCoterm {
    ticket: string
    node: string
    vmid: number
    fqdn: string
    port: number
}

interface ConsoleCredentialsWithCoterm {
    isTlsEnabled: boolean
    fqdn: string
    port: number
    token: string
}

export type ConsoleCredentials =
    | ConsoleCredentialsWithoutCoterm
    | ConsoleCredentialsWithCoterm

export const rawDataToConsoleCredentials = (data: any): ConsoleCredentials => {
    if (data.is_tls_enabled !== undefined) {
        return {
            isTlsEnabled: data.is_tls_enabled,
            fqdn: data.fqdn,
            port: data.port,
            token: data.token,
        }
    } else {
        return {
            ticket: data.ticket,
            node: data.node,
            vmid: data.vmid,
            fqdn: data.fqdn,
            port: data.port,
        }
    }
}

export default (
    uuid: string,
    consoleType: ConsoleType
): Promise<ConsoleCredentials> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/create-console-session`, {
            type: consoleType,
        })
            .then(({ data: { data } }) =>
                resolve(rawDataToConsoleCredentials(data))
            )
            .catch(reject)
    })
}