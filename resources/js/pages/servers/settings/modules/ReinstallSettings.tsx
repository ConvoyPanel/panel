import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, Select, TextInput } from '@mantine/core'
import { useQuery } from '@tanstack/react-query'
import { ChangeEvent, FormEvent, useContext, useEffect, useMemo, useState } from 'react'
import getTemplates from '@/api/server/settings/getTemplates'

const ReinstallSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const [selectedTemplate, setSelectedTemplate] = useState<number>()

  const { data, status } = useQuery(['templates'], async () => {
    const { data } = await getTemplates()
    return data
  })

  const templates = useMemo(() => {
    if (status === 'success') {
      return data.map(template => ({
        value: template.id as unknown as string,
        label: template.name
      }))
    }

    return []
  }, [data, status])

  const [processing, setProcessing] = useState(false)

  const submit = (e: FormEvent<HTMLFormElement>) => {}

  /* useEffect(() => {
    if (!processing) {
      Inertia.reload({ only: ['server'] })
    }
  }, [processing]) */

  return (
    <FormBlock
      title='Rebuild Server'
      inputs={
        <>
          <Select
            label='Template'
            value={selectedTemplate as unknown as string}
            onChange={(e) => setSelectedTemplate(e as unknown as number)}
            data={templates}
            //error={errors.compression}
          />
        </>
      }
      actions={
        <Button color='red' type='submit' className='ml-4' loading={processing}>
          Rebuild
        </Button>
      }
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default ReinstallSettings
