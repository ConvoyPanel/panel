import getBackups, { BackupResponse } from '@/api/server/backups/getBackups'
import Button from '@/components/elements/Button'
import Modal from '@/components/elements/Modal'
import Display from '@/components/elements/displays/DisplayRow'
import TextInput from '@/components/elements/inputs/TextInput'
import Spinner from '@/components/elements/Spinner'
import ServerContentBlock from '@/components/servers/ServerContentBlock'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { formatDistanceToNow } from 'date-fns'
import { useEffect, useState } from 'react'
import useSWR from 'swr'
import { useFormik } from 'formik'
import * as yup from 'yup'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import createBackup from '@/api/server/backups/createBackup'
import useFlash from '@/util/useFlash'
import Pagination from '@/components/elements/Pagination'
import { useLocation } from 'react-router-dom'
import usePagination from '@/util/usePagination'

const BackupsContainer = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)

  const [open, setOpen] = useState(false)
  const [page, setPage] = usePagination()

  const { data, mutate } = useSWR(['server:backups', uuid, page], () =>
    getBackups(uuid, {
      page
    })
  )

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
    <ServerContentBlock title='Backups'>
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
      {!data ? (
        <Spinner />
      ) : data.pagination.total === 0 ? (
        <p className='text-sm text-center'>There are no backups</p>
      ) : (
        <Pagination data={data} onPageSelect={setPage}>
          {({ items }) => (
            <Display.Group>
              {items.map((backup) => (
                <Display.Row
                  key={backup.uuid}
                  className='grid-cols-1 md:grid-cols-8 text-sm'
                >
                  <div className='md:col-span-5'>
                    <p className='overflow-hidden text-ellipsis font-semibold text-foreground'>
                      {backup.name}
                    </p>
                  </div>
                  <div>{bytesToString(backup.size)}</div>
                  <div className='md:col-span-2'>
                    {formatDistanceToNow(backup.createdAt, {
                      includeSeconds: true,
                      addSuffix: true,
                    })}
                  </div>
                </Display.Row>
              ))}
            </Display.Group>
          )}
        </Pagination>
      )}
    </ServerContentBlock>
  )
}

export default BackupsContainer
