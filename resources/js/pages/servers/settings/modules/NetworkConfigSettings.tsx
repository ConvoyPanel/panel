import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const NetworkConfigSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const existingNameservers = settingsContext?.config.nameserver?.split(',')

  const { data, setData, put, processing, errors, reset } = useForm({
    hostname: settingsContext?.config.searchdomain,
    nameservers: [
      existingNameservers ? existingNameservers[0] : '',
      existingNameservers ? existingNameservers[1] : '',
    ],
  })

  const submit = () => {
    put(
      route(
        'servers.show.settings.update-network-config',
        settingsContext?.server.id
      )
    )
  }

  useEffect(() => {
    if (!processing) {
      Inertia.reload({ only: ['config'] })
    }
  }, [processing])

  const setNameserver = (index: number, value: string) => {
    const nameservers = [...(data.nameservers as string[])]
    nameservers[index] = value
    setData('nameservers', nameservers)
  }

  return (
    <FormBlock
      title='Network Configuration'
      inputs={
        <>
          <TextInput
            label='Hostname'
            name='name'
            value={data.hostname}
            className='mt-1 block w-full'
            //@ts-ignore
            onChange={(e) => setData('hostname', e.target.value)}
            error={errors.hostname}
          />
          <TextInput
            label='Nameserver 1'
            name='name'
            value={data.nameservers[0]}
            className='mt-1 block w-full'
            onChange={(e) => setNameserver(0, e.target.value)}
            error={errors.nameservers ? errors.nameservers[0] : null}
          />
          <TextInput
            label='Nameserver 2'
            name='name'
            value={data.nameservers[1]}
            className='mt-1 block w-full'
            onChange={(e) => setNameserver(1, e.target.value)}
            error={errors.nameservers ? errors.nameservers[1] : null}
          />
        </>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default NetworkConfigSettings
