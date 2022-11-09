import ServerDetailsBlock from '@/components/servers/overview/ServerDetailsBlock'
import ServerPowerBlock from '@/components/servers/overview/ServerPowerBlock'
import ServerContentBlock from '@/components/servers/ServerContentBlock'

const ServerOverviewContainer = () => {
  return (
    <ServerContentBlock title='Overview'>
      <ServerPowerBlock />
      <ServerDetailsBlock />
    </ServerContentBlock>
  )
}

export default ServerOverviewContainer
