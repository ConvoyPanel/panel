import flashes, { FlashStore } from '@/state/flashes'
import settings, { SettingsStore } from '@/state/settings'
import user, { UserStore } from '@/state/user'
import { createStore, createTypedHooks } from 'easy-peasy'

export interface ApplicationStore {
    user: UserStore
    settings: SettingsStore
    flashes: FlashStore
}

const state: ApplicationStore = {
    user,
    settings,
    flashes,
}

const typedHooks = createTypedHooks<ApplicationStore>()

export const useStoreActions = typedHooks.useStoreActions
export const useStoreDispatch = typedHooks.useStoreDispatch
export const useStoreState = typedHooks.useStoreState

export const store = createStore(state)