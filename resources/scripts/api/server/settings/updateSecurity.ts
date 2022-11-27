import http from '@/api/http'

type UpdatePasswordPayload = {
    type: 'cipassword'
    password?: string
}

type UpdateKeysPayload = {
    type: 'sshkeys'
    sshKeys?: string
}

type Payload = UpdatePasswordPayload | UpdateKeysPayload

export default (uuid: string, data: Payload) => {
    let payload = {}

    if (data.type === 'cipassword') {
        payload = {
            type: data.type,
            password: data.password
        }
    }

    if (data.type === 'sshkeys') {
        payload = {
            type: data.type,
            ssh_keys: data.sshKeys
        }
    }

    return http.put(`/api/client/servers/${uuid}/settings/security`, payload)
}
