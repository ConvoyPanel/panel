import { Action, action } from 'easy-peasy'

export interface UserData {
    name: string
    email: string
    rootAdmin: boolean
    createdAt: string
    updatedAt: string
}

export interface UserStore {
    data?: UserData
    setUserData: Action<UserStore, UserData>
}

const user: UserStore = {
    data: undefined,
    setUserData: action((state, payload) => {
        state.data = payload
    }),
}

export default user