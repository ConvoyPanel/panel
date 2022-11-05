import { Server } from '@/api/server/getServer'
import { action, Action, thunk, Thunk } from 'easy-peasy'

export interface ServerStore {
    data?: Server
    setServer: Action<ServerStore, Server>
    getServer: Thunk<ServerStore, string>
}

const Server: ServerStore = {
    data: undefined,
    setServer: action((state, payload) => {
        state.data = payload
    }),
    getServer: thunk(async (state, payload) => {
        //const server = await getServers
    })
}