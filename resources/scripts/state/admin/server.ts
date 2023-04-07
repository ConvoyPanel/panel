import { AdminServer } from '@/api/admin/servers/getServer'
import getStatus, { ServerStateData } from '@/api/server/getState'
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy'
import isEqual from 'react-fast-compare'
import { getServer } from '@/api/admin/servers/getServer'

export interface ServerDataStore {
    data?: AdminServer
    setServer: Action<ServerDataStore, AdminServer>
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
