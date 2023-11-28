import http from '@/api/http'

type UpdatePasswordPayload = {
    type: 'password'
    password?: string
}

type UpdateKeysPayload = {
    type: 'ssh_keys'
    sshKeys?: string
}

type Payload = UpdatePasswordPayload | UpdateKeysPayload

const updateAuthSettings = (uuid: string, data: Payload) => {
    let payload = {}

    if (data.type === 'password') {
        payload = {
            type: data.type,
            password: data.password,
        }
    }

    if (data.type === 'ssh_keys') {
        payload = {
            type: data.type,
            ssh_keys: data.sshKeys,
        }
    }

    return http.put(`/api/client/servers/${uuid}/settings/auth`, payload)
}

export default updateAuthSettings