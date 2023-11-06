import { Action, Thunk, action, createContextStore, thunk } from 'easy-peasy'
import isEqual from 'react-fast-compare'

import { AdminServerBuild, getServer } from '@/api/admin/servers/getServer'

export interface ServerDataStore {
    data?: AdminServerBuild
    setServer: Action<ServerDataStore, AdminServerBuild>
    getServer: Thunk<ServerDataStore, string>
}

const server: ServerDataStore = {
    data: undefined,
    setServer: action((state, payload) => {
        if (!isEqual(payload, state.data)) {
            state.data = payload
        }
    }),
    getServer: thunk(async (actions, uuid) => {
        const server = await getServer(uuid)

        actions.setServer(server)
    }),
}

interface ServerStore {
    server: ServerDataStore
    clearServerState: Action<ServerStore>
}

export const AdminServerContext = createContextStore<ServerStore>({
    server,
    clearServerState: action(state => {
        state.server.data = undefined
    }),
})