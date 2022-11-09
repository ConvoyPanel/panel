import ServerDetailsBlock from '@/components/servers/overview/ServerDetailsBlock'
import ServerPowerBlock from '@/components/servers/overview/ServerPowerBlock'
import ServerTerminalBlock from '@/components/servers/overview/ServerTerminalBlock'
import ServerContentBlock from '@/components/servers/ServerContentBlock'

const ServerOverviewContainer = () => {
  return (
    <ServerContentBlock title='Overview'>
      <ServerPowerBlock />
      <div className='grid grid-cols-10 gap-6'>
        <ServerDetailsBlock />
        <ServerTerminalBlock />
      </div>
    </ServerContentBlock>
  )
}

export default ServerOverviewContainer
