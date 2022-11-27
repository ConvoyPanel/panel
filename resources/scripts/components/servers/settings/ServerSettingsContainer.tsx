import Button from '@/components/elements/Button'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import GeneralContainer from '@/components/servers/settings/GeneralContainer'
import HardwareContainer from '@/components/servers/settings/HardwareContainer'
import NetworkingContainer from '@/components/servers/settings/NetworkingContainer'

const ServerSettingsContainer = () => {
  return (
    <ServerContentBlock title='Settings' showFlashKey='settings'>
        <GeneralContainer />
        <FormSection.Divider />
        <HardwareContainer />
        <FormSection.Divider />
        <FormSection title='Security'>
          <FormCard className='w-full'>
            <FormCard.Body>
              <FormCard.Title>gaming</FormCard.Title>
              <p>gaming</p>
            </FormCard.Body>
            <FormCard.Footer>
              <Button variant='filled' color='success' size='sm'>
                Save
              </Button>
            </FormCard.Footer>
          </FormCard>
        </FormSection>
        <FormSection.Divider />
        <NetworkingContainer />
    </ServerContentBlock>
  )
}

export default ServerSettingsContainer
