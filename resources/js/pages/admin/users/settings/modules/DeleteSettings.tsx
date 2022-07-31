import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/users/settings/Index'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Modal, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect, useState } from 'react'

const DeleteSettings = () => {
  const settingsContext = useContext(SettingsContext)
  const [showConfirmation, setShowConfirmation] = useState(false)

  const { delete: deleteUser, processing, errors } = useForm()

  const submit = (e: FormEvent<HTMLFormElement>) => {
    //deleteNode(route('admin.nodes.destroy', settingsContext?.node.id))
  }

  const handleDelete = () => {
    deleteUser(route('admin.users.show', settingsContext?.user.id))
  }

  return (
    <FormBlock
      title='Delete User'
      inputs={
        <>
          <Modal
            opened={showConfirmation}
            onClose={() => setShowConfirmation(false)}
            title={`Delete ${settingsContext?.user.name}?`}
            centered
          >
            <p className='p-desc'>
              Are you sure you want to delete this user? All servers associated with this account won't be deleted from the actual node. Purge all servers before deleting this user.
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
            Delete the user and all the data associated with it. This will not delete the actual server files from the node.
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
