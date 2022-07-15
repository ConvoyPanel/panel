import server, { ServerStore } from '@/state/server'
import { createStore } from 'easy-peasy'

export interface ApplicationStore {
    server: ServerStore
}

const state: ApplicationStore = {
    server,
}

export const store = createStore(state)