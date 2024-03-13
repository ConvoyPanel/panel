import { useStoreState } from '@/state'

import ServerContentBlock from '@/components/servers/ServerContentBlock'
import ServerAdminBlock from '@/components/servers/overview/ServerAdminBlock'
import ServerDetailsBlock from '@/components/servers/overview/ServerDetailsBlock'
import ServerNetworkBlock from '@/components/servers/overview/ServerNetworkBlock'
import ServerPowerBlock from '@/components/servers/overview/ServerPowerBlock'
import ServerTerminalBlock from '@/components/servers/overview/ServerTerminalBlock'


const ServerOverviewContainer = () => {
    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin)
    return (
        <ServerContentBlock title='Overview'>
            <ServerPowerBlock />
            <div className='grid grid-cols-10 gap-6'>
                <ServerDetailsBlock />
                <ServerNetworkBlock />
                <ServerTerminalBlock />
                {rootAdmin && <ServerAdminBlock />}
            </div>
        </ServerContentBlock>
    )
}

export default ServerOverviewContainer