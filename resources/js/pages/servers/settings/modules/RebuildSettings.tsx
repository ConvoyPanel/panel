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

const RebuildSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const [showModal, setShowModal] = useState(false)
  const [confirmOne, setConfirmOne] = useState('')
  const confirmText = useMemo(() => {
    return `${settingsContext!.auth.user.name} will lose all data on this server`
  }, [settingsContext])
  const isConfirmed = useMemo(() => {
    return confirmOne === confirmText
  }, [confirmOne, confirmText])

  const { data, status } = useQuery(['templates'], async () => {
    const { data } = await getTemplates(settingsContext!.server.id)
    return data
  })

  const { post, data: formData, processing, setData } = useForm({
    template_id: '',
  })

  const templates = useMemo(() => {
    if (status === 'success') {
      return data.map((template) => ({
        value: template.id.toString(),
        label: template.server.name,
      }))
    }

    return []
  }, [data, status])

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setShowModal(true)
  }

  const handleRebuild = async () => {
    post(route('servers.show.settings.rebuild', settingsContext?.server.id))
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
              onClick={() => handleRebuild()}
            >
              I understand the consequences.
            </Button>
          </Modal>
          <Select
            label='Template'
            value={formData.template_id}
            onChange={(e) => setData('template_id', e as string)}
            data={templates}
          />
        </>
      }
      actions={
        <Button color='red' type='submit' className='ml-4' disabled={formData.template_id === ''} loading={processing}>
          Rebuild
        </Button>
      }
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default RebuildSettings
