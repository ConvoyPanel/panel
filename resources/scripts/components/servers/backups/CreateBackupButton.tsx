import useFlash from '@/util/useFlash'
import { useFormik } from 'formik'
import * as yup from 'yup'
import createBackup from '@/api/server/backups/createBackup'
import { useEffect, useState } from 'react'
import { ServerContext } from '@/state/server'
import { KeyedMutator } from 'swr'
import { BackupResponse } from '@/api/server/backups/getBackups'
import Modal from '@/components/elements/Modal'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInput from '@/components/elements/inputs/TextInput'
import Button from '@/components/elements/Button'

interface Props {
  swr: {
    mutate: KeyedMutator<BackupResponse>
  }
}

const CreateBackupButton = ({ swr: { mutate } }: Props) => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const [open, setOpen] = useState(false)
  const { clearFlashes, clearAndAddHttpError } = useFlash()

  const form = useFormik({
    initialValues: {
      name: '',
    },
    validationSchema: yup.object({
      name: yup.string().required('A name is required').max(40),
    }),
    onSubmit: (values, { setSubmitting }) => {
      clearFlashes('backups:create')

      createBackup(uuid, {
        name: values.name,
        locked: false,
        mode: 'snapshot',
        compressionType: 'zstd',
      })
        .then((backup) => {
          console.log({ backup })
          mutate(
            (data) =>
              ({
                ...data,
                items: data!.items.concat(backup),
                backupCount: data!.backupCount + 1,
              } as BackupResponse),
            false
          )
          setSubmitting(false)
        })
        .catch((error) => {
          clearAndAddHttpError({ key: 'backups:create', error })
          setSubmitting(false)
        })
    },
  })

  useEffect(() => {
    clearFlashes('backups:create')
    form.resetForm()
  }, [open])

  const handleClose = () => {
    if (!form.isSubmitting) setOpen(false)
  }

  return (
    <>
      <Modal open={open} onClose={handleClose}>
        <Modal.Header>
          <Modal.Title>Create a Backup</Modal.Title>
        </Modal.Header>
        <form onSubmit={form.handleSubmit}>
          <Modal.Body>
            <Modal.Description bottomMargin>
              Creating a backup will take a copy of your server files. This can
              take a while depending on the size of your server.
            </Modal.Description>

            <FlashMessageRender byKey={'backups:create'} className='mb-5' />

            <TextInput
              value={form.values.name}
              onChange={form.handleChange}
              error={form.touched.name ? form.errors.name : undefined}
              name='name'
              placeholder='Name'
            />
          </Modal.Body>
          <Modal.Actions>
            <Modal.Action onClick={handleClose}>Cancel</Modal.Action>
            <Modal.Action type='submit' loading={form.isSubmitting}>
              Create
            </Modal.Action>
          </Modal.Actions>
        </form>
      </Modal>
      <div className='flex justify-end mb-3'>
        <Button onClick={() => setOpen(true)} variant='filled'>
          New Backup
        </Button>
      </div>
    </>
  )
}

export default CreateBackupButton
