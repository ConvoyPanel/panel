import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy'
import { User } from '@/api/admin/users/getUsers'
import { ServerDataStore, ServerStatusStore } from '@/state/server'
import getUser from '@/api/admin/users/getUser'

interface UserDataStore {
    data?: User
    setUser: Action<UserDataStore, User>
    getUser: Thunk<UserDataStore, number>
}

const user: UserDataStore = {
    data: undefined,
    setUser: action((state, payload) => {
        state.data = payload
    }),
    getUser: thunk(async (actions, id) => {
        const user = await getUser(id)

        actions.setUser(user)
    }),
}

interface UserStore {
    user: UserDataStore
    clearUserState: Action<UserStore>
}

export const AdminUserContext = createContextStore<UserStore>({
    user,
    clearUserState: action(state => {
        state.user.data = undefined
    }),
})
