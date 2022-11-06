import ServerDetailsBlock from '@/components/servers/overview/ServerDetailsBlock'
import ServerContentBlock from '@/components/servers/ServerContentBlock'

const ServerOverviewContainer = () => {
  return (
    <ServerContentBlock title='Overview'>
      <ServerDetailsBlock />
    </ServerContentBlock>
  )
}

export default ServerOverviewContainer
