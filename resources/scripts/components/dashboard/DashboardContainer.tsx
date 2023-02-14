import ServersContainer from '@/components/dashboard/ServersContainer'
import PageContentBlock from '@/components/elements/PageContentBlock'

const DashboardContainer = () => {
  return (
    <PageContentBlock title='Dashboard' showFlashKey='dashboard'>
      <ServersContainer />
    </PageContentBlock>
  )
}

export default DashboardContainer
