import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/nodes/settings/Index'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Modal, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect, useState } from 'react'

const DeleteSettings = () => {
  const settingsContext = useContext(SettingsContext)
  const [showConfirmation, setShowConfirmation] = useState(false)

  const { delete: deleteNode, processing, errors } = useForm()

  const submit = (e: FormEvent<HTMLFormElement>) => {
    //deleteNode(route('admin.nodes.destroy', settingsContext?.node.id))
  }

  const handleDelete = () => {
    deleteNode(route('admin.nodes.destroy', settingsContext?.node.id))
  }

  return (
    <FormBlock
      title='Delete Node'
      inputs={
        <>
          <Modal
            opened={showConfirmation}
            onClose={() => setShowConfirmation(false)}
            title={`Delete ${settingsContext?.node.name}?`}
            centered
          >
            <p className='p-desc'>
              Are you sure you want to delete this node? This action cannot be undone.
            </p>

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
            Delete the node and all of its associated data. This will only
            remove the node from Convoy user interface, not the actual node and
            the servers.
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
