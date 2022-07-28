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

  const { data, setData, delete: deleteNode, processing, errors } = useForm({
    purge: false
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    //deleteNode(route('admin.nodes.destroy', settingsContext?.node.id))
  }

  const handleDelete = () => {
    deleteNode(route('admin.servers.destroy', settingsContext?.server.id))
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
              checked={data.purge}
              className='mt-3'
              onChange={(e) => setData('purge', e.target.checked)}
              label='Purge server from node (delete all data)'
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
            Delete the server and all of its associated data. This can
            remove the server from Convoy user interface and the Proxmox node.
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
