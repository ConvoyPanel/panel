import getServer, { Server } from '@/api/server/getServer'
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy'

export interface ServerDataStore {
  data?: Server
  setServer: Action<ServerDataStore, Server>
  getServer: Thunk<ServerDataStore, string>
}

const server: ServerDataStore = {
  data: undefined,
  setServer: action((state, payload) => {
    state.data = payload
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

export const ServerContext = createContextStore<ServerStore>({
  server: server,
  clearServerState: action((state) => {
    state.server.data = undefined
  }),
})
