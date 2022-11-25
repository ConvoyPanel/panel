import renameServer from '@/api/server/settings/renameServer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import TextInput from '@/components/elements/inputs/TextInput'
import Modal from '@/components/elements/Modal'
import { ServerContext } from '@/state/server'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { useFormik } from 'formik'
import { useState } from 'react'
import * as yup from 'yup'

const GeneralContainer = () => {
  const server = ServerContext.useStoreState((state) => state.server.data!)
  const setServer = ServerContext.useStoreActions(
    (actions) => actions.server.setServer
  )
  const { clearFlashes, clearAndAddHttpError } = useFlash()
  const notify = useNotify()
  const [isModalOpen, setIsModalOpen] = useState(false)

  const form = useFormik({
    initialValues: {
      name: server.name,
      hostname: server.hostname,
    },
    validationSchema: yup.object({
      name: yup.string().required('A name is required').max(40),
      hostname: yup
        .string()
        .matches(
          /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
          'Enter a valid hostname'
        ),
    }),
    onSubmit: ({ name, hostname }, { setSubmitting }) => {
      clearFlashes('servers:settings:general')

      renameServer(server.uuid, { name, hostname })
        .then(() => {
          notify({
            title: 'Updated',
            message: 'Updated general settings',
            color: 'green',
          })
          setServer({ ...server, name })
          setSubmitting(false)
        })
        .catch((error) => {
          clearAndAddHttpError({ key: 'servers:settings:general', error })
          setSubmitting(false)
          notify({
            title: 'Error',
            message: 'Failed to update general settings',
            color: 'red',
          })
        })
    },
  })

  return (
    <FormSection title='General Settings'>
      <FormCard className='w-full'>
        <form onSubmit={form.handleSubmit}>
          <FormCard.Body>
            <FormCard.Title>Server Name</FormCard.Title>
            <div className='mt-3'>
              <FlashMessageRender byKey='servers:settings:general' />
              <TextInput
                value={form.values.name}
                onChange={form.handleChange}
                error={form.touched.name ? form.errors.name : undefined}
                disabled={form.isSubmitting}
                name='name'
                label='Display Name'
              />
              <TextInput
                value={form.values.hostname}
                onChange={form.handleChange}
                error={form.touched.hostname ? form.errors.hostname : undefined}
                disabled={form.isSubmitting}
                name='hostname'
                wrapperClassName='mt-3'
                label='Hostname'
              />
            </div>
          </FormCard.Body>
          <FormCard.Footer className='flex justify-center md:justify-end'>
            <Button loading={form.isSubmitting} type='submit' variant='filled' color='success' size='sm'>
              Save
            </Button>
          </FormCard.Footer>
        </form>
      </FormCard>
      <Modal open={isModalOpen} onClose={() => setIsModalOpen(false)}>
        <Modal.Header>
          <Modal.Title>Reinstall Server?</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Modal.Description bottomMargin>
            Are you sure you want to reinstall this server? This is a
            destructive action and will remove all data on your server.
          </Modal.Description>
        </Modal.Body>
        <Modal.Actions>
          <Modal.Action onClick={() => setIsModalOpen(false)}>
            Cancel
          </Modal.Action>
          <Modal.Action type='submit'>Reinstall</Modal.Action>
        </Modal.Actions>
      </Modal>
      <FormCard className='w-full'>
        <FormCard.Body>
          <FormCard.Title>Reinstall Server</FormCard.Title>
          <p className='description-small mt-3'>
            Start your server on a fresh slate. Select a template and confirm
            the reinstallation.
          </p>
        </FormCard.Body>
        <FormCard.Footer className='flex justify-center md:justify-end'>
          <Button
            onClick={() => setIsModalOpen(true)}
            variant='filled'
            color='danger'
            size='sm'
          >
            Reinstall
          </Button>
        </FormCard.Footer>
      </FormCard>
    </FormSection>
  )
}

export default GeneralContainer
