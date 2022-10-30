import ServerContainer from '@/components/dashboard/ServerContainer'
import PageContentBlock from '@/components/elements/PageContentBlock'

const DashboardContainer = () => {
  return (
    <PageContentBlock title='Dashboard' showFlashKey='dashboard'>
      <ServerContainer />
    </PageContentBlock>
  )
}

export default DashboardContainer
