import { AdminServerContext } from '@/state/admin/server'
import { useState } from 'react'

import updateServer from '@/api/admin/servers/updateServer'

import Button from '@/components/elements/Button'

const RestoreAccessButton = () => {
    const [loading, setLoading] = useState(false)
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )

    const handle = async () => {
        setLoading(true)
        await updateServer(server.uuid, { status: null })

        setServer({
            ...server,
            status: null,
        })
        setLoading(false)
    }

    return (
        <Button
            onClick={handle}
            loading={loading}
            variant={'filled'}
            className={'mt-6'}
        >
            Restore Access
        </Button>
    )
}

export default RestoreAccessButton
