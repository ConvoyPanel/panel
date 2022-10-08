import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { Inertia } from '@inertiajs/inertia'
import { Button } from '@mantine/core'
import { FormEvent, useContext } from 'react'

const VncRedirectSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const submit = (e: FormEvent<HTMLFormElement>) => {}

  const redirect = () => {
    Inertia.visit(
      route('servers.show.security.vnc', settingsContext?.server.uuidShort)
    )
  }

  return (
    <FormBlock
      title='NoVNC Terminal'
      inputs={<>
        <p className='p-desc'>Remotely access your server terminal from the web.</p>
      </>}
      actions={
        <Button onClick={redirect}>
          Start Session
        </Button>
      }
      onSubmit={submit}
      processing={false}
    />
  )
}

export default VncRedirectSettings
