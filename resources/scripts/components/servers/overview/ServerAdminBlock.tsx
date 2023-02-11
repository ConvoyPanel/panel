import Button from '@/components/elements/Button'
import Card from '@/components/elements/Card'
import { ServerContext } from '@/state/server'
import { Link } from 'react-router-dom'

const ServerAdminBlock = () => {
    const serverId = ServerContext.useStoreState(state => state.server.data!.id)

    return (
        <Card className='flex flex-col justify-between col-span-10 md:col-span-5 relative'>
            <div>
                <h5 className='h5'>Configure This Server</h5>
                <p className='description-small mt-1'>
                    You are an administrator! You can click below to visit this server's build configuration and swiftly
                    make edits.
                </p>
            </div>
            <div className='flex mt-6 justify-end'>
                <Link to={`/admin/servers/${serverId}`}>
                    <Button className='inline-flex items-center' as={'span'}>
                        Configure Server
                    </Button>
                </Link>
            </div>
        </Card>
    )
}

export default ServerAdminBlock
