import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Radio, RadioGroup } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const BiosConfigSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, post, processing, errors, reset } = useForm({
    _method: 'put',
    type: settingsContext?.config.bios,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    post(
      route('servers.show.settings.update-bios', settingsContext?.server.id)
    )
  }

  useEffect(() => {
    if (!processing) {
      Inertia.reload({ only: ['settings'] })
    }
  }, [processing])
  //const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => formDataHandler(event, setData)

  return (
    <FormBlock
      title='BIOS Configuration'
      inputs={
        <>
          <RadioGroup
            value={data.type}
            error={errors.type}
            //@ts-ignore
            onChange={(value) => setData('type', value)}
          >
            <Radio label='SeaBIOS' value='seabios'/>
            <Radio label='OVMF' value='ovmf'/>
          </RadioGroup>
        </>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default BiosConfigSettings
