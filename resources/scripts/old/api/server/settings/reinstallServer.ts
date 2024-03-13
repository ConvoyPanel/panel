import http from '@/api/http'

interface ReinstallServerParameters {
    templateUuid: string
    accountPassword: string
    startOnCompletion: boolean
}

const reinstallServer = (
    serverUuid: string,
    {
        templateUuid,
        accountPassword,
        startOnCompletion,
    }: ReinstallServerParameters
) => {
    return http.post(`/api/client/servers/${serverUuid}/settings/reinstall`, {
        template_uuid: templateUuid,
        account_password: accountPassword,
        start_on_completion: startOnCompletion,
    })
}

export default reinstallServer