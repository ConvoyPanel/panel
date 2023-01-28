import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import Button from '@/components/elements/Button'
import { AdminServerContext } from '@/state/admin/server'
import { Link } from 'react-router-dom'

const ServerOverviewContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)

    return (
        <ServerContentBlock title={'Overview'}>
            <Link to={`/servers/${server.id}`}>
                <Button className='inline-flex items-center' as={'span'}>
                    Enter Server Console
                </Button>
            </Link>
        </ServerContentBlock>
    )
}

export default ServerOverviewContainer
