import { createTypedHooks } from 'easy-peasy'
import { ApplicationStore } from '@/state'
import { useEffect } from 'react'

const { useStoreActions, useStoreState } = createTypedHooks<ApplicationStore>()

const useServerState = (id: number) => {
    const server = useStoreState(state => state.server.data)
    const setServer = useStoreActions(actions => actions.server.setServer)

    // preflight check to make sure we have the right server
    useEffect(() => {
        if (server && server.id !== id) {
            setServer(undefined)
        }
    }, [])


}

export default useServerState