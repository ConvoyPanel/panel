import axios from '@/lib/axios.ts'

export interface ReinstallServerRequest {
    templateUuid: string
    accountPassword?: string
    startOnCompletion?: boolean
}

const reinstallServer = async (uuid: string, data: ReinstallServerRequest) => {
    await axios.post(`/api/client/servers/${uuid}/settings/reinstall`, {
        template_uuid: data.templateUuid,
        account_password: data.accountPassword,
        start_on_completion: data.startOnCompletion,
    })
}

export default reinstallServer
