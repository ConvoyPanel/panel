import progress, { ProgressStore } from '@/state/progress'
import { createStore, createTypedHooks } from 'easy-peasy'

export interface ApplicationStore {
  progress: ProgressStore
}

const state: ApplicationStore = {
  progress,
}

const typedHooks = createTypedHooks<ApplicationStore>()

export const useStoreActions = typedHooks.useStoreActions
export const useStoreDispatch = typedHooks.useStoreDispatch
export const useStoreState = typedHooks.useStoreState

export const store = createStore(state)
