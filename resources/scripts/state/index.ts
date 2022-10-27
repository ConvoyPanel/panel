import progress, { ProgressStore } from '@/state/progress';
import { createStore } from 'easy-peasy'

export interface ApplicationStore {
    progress: ProgressStore;
}

const state: ApplicationStore = {
    progress
}

export const store = createStore(state)