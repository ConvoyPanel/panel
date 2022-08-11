import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/servers/settings/Index'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Checkbox, Modal, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect, useState } from 'react'

const DeleteSettings = () => {
  const settingsContext = useContext(SettingsContext)
  const [showConfirmation, setShowConfirmation] = useState(false)

  const { data, setData, delete: deleteServer, processing, errors } = useForm({
    'no_purge': false,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    //deleteNode(route('admin.nodes.destroy', settingsContext?.node.id))
  }

  const handleDelete = () => {
    deleteServer(route('admin.servers.show', settingsContext?.server.id))
  }

  return (
    <FormBlock
      title='Delete Server'
      inputs={
        <>
          <Modal
            opened={showConfirmation}
            onClose={() => setShowConfirmation(false)}
            title={`Delete ${settingsContext?.server.name}?`}
            centered
          >
            <p className='p-desc'>
              Are you sure you want to delete this server? This action cannot be undone.
            </p>


            <Checkbox
              checked={data.no_purge}
              onChange={(e) => setData('no_purge', e.target.checked)}
              label='Do not purge virtual machine'
            />

            <Button
              loading={processing}
              color='red'
              className='mt-3'
              fullWidth
              onClick={() => handleDelete()}
            >
              Delete
            </Button>
          </Modal>
          <p className='p-desc'>
            Delete the server and all of its associated data. This action will also permanently delete the virtual machine from the node as well.
          </p>
        </>
      }
      actions={<Button onClick={() => setShowConfirmation(true)} color='red'>Delete</Button>}
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default DeleteSettings
