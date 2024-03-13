import { Action, Thunk, action, createContextStore, thunk } from 'easy-peasy'
import isEqual from 'react-fast-compare'

import getServer, { ServerBuild } from '@/api/server/getServer'
import getStatus, { ServerStateData } from '@/api/server/getState'


export interface ServerDataStore {
    data?: ServerBuild
    setServer: Action<ServerDataStore, ServerBuild>
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

export interface ServerStatusStore {
    data?: ServerStateData
    getStatus: Thunk<ServerStatusStore, string>
    setStatus: Action<ServerStatusStore, ServerStateData>
}

const status: ServerStatusStore = {
    data: undefined,
    getStatus: thunk(async (actions, uuid) => {
        const status = await getStatus(uuid)

        actions.setStatus(status)
    }),

    setStatus: action((state, payload) => {
        if (!isEqual(payload, state.data)) {
            state.data = payload
        }
    }),
}

interface ServerStore {
    server: ServerDataStore
    status: ServerStatusStore
    clearServerState: Action<ServerStore>
}

export const ServerContext = createContextStore<ServerStore>({
    server,
    status,
    clearServerState: action(state => {
        state.server.data = undefined
        state.status.data = undefined
    }),
})