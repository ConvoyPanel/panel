import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Select, TextInput } from '@mantine/core'
import { useQuery } from '@tanstack/react-query'
import {
  ChangeEvent,
  FormEvent,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react'
import getTemplates from '@/api/server/settings/getTemplates'
import reinstallServer from '@/api/server/settings/reinstallServer'

const ReinstallSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const [showModal, setShowModal] = useState(false)
  const [confirmOne, setConfirmOne] = useState('')
  const confirmText = useMemo(() => {
    return `${settingsContext!.auth.user.name} will lose all data on this server.`
  }, [settingsContext])
  const isConfirmed = useMemo(() => {
    return confirmOne === confirmText
  }, [confirmOne, confirmText])

  const [selectedTemplate, setSelectedTemplate] = useState<string>()

  const { data, status } = useQuery(['templates'], async () => {
    const { data } = await getTemplates(settingsContext!.server.id)
    return data
  })

  const templates = useMemo(() => {
    if (status === 'success') {
      return data.map((template) => ({
        value: template.id as unknown as string,
        label: template.server.name,
      }))
    }

    return []
  }, [data, status])

  const [processing, setProcessing] = useState(false)

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setShowModal(true)
  }

  const handleReinstall = async () => {
    setProcessing(true)
    await reinstallServer(
      selectedTemplate as unknown as number,
      settingsContext!.server.id
    )
    setProcessing(false)
  }

  return (
    <FormBlock
      title='Rebuild Server'
      inputs={
        <>
          <Modal
            opened={showModal}
            onClose={() => setShowModal(false)}
            title={`Rebuild server?`}
            centered
          >
            <p className='p-desc'>
              Are you sure you want to rebuild this server? This will delete all
              data on the server and replace it with the data from the selected
              template. Type the confirmation text below to rebuild the server.
            </p>

            <p className='text-sm font-bold text-gray-800 mt-3'>
              { confirmText }
            </p>


            <TextInput
              label='Confirm Here'
              value={confirmOne}
              className='mt-1 block w-full'
              autoFocus
              onChange={(e) => setConfirmOne(e.target.value)}
              required
            />

            <Button
              loading={processing}
              color='red'
              className='mt-3'
              fullWidth
              disabled={!isConfirmed}
              onClick={() => handleReinstall()}
            >
              I understand the consequences.
            </Button>
          </Modal>
          <Select
            label='Template'
            value={selectedTemplate as unknown as string}
            onChange={(e) => setSelectedTemplate(e as string)}
            data={templates}
            //error={errors.compression}
          />
        </>
      }
      actions={
        <Button color='red' type='submit' className='ml-4' disabled={selectedTemplate === undefined} loading={processing}>
          Rebuild
        </Button>
      }
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default ReinstallSettings
