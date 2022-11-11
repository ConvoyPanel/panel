import ServerContainer from '@/components/dashboard/ServerContainer'
import Button from '@/components/elements/Button'
import PageContentBlock from '@/components/elements/PageContentBlock'

const DashboardContainer = () => {
  return (
    <PageContentBlock title='Dashboard' showFlashKey='dashboard'>
      <Button>Start</Button>
      <ServerContainer />
    </PageContentBlock>
  )
}

export default DashboardContainer
