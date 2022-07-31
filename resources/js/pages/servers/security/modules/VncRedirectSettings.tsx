import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, Radio, RadioGroup, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const VncRedirectSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const submit = (e: FormEvent<HTMLFormElement>) => {}

  const redirect = () => {
    Inertia.visit(
      route('servers.show.security.vnc', settingsContext?.server.id)
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
