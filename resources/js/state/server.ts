import { action, Action } from 'easy-peasy'
import { ServerState, FormattedBytes } from '@/api/server/getStatus'

export interface MemUnparsed {
    mem: number;
    maxmem: number;
}

export interface ServerData {
  id: number
  state: ServerState
  cpu: number
  mem: FormattedBytes
  maxmem: FormattedBytes
  memUnparsed: MemUnparsed
}

export interface ServerStore {
  data?: ServerData
  setServer: Action<ServerStore, ServerData | undefined>
}

const server: ServerStore = {
    data: undefined,
    setServer: action((state, payload) => {
        state.data = payload
    })
}

export default server;