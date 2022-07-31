import { formDataHandler } from '@/util/helpers'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Checkbox, Modal, TextInput } from '@mantine/core'
import { ChangeEvent } from 'react'

interface Props {
  open: boolean
  setOpen: (open: boolean) => void
}

const NewUserModal = ({ open, setOpen }: Props) => {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    root_admin: false,
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = () => {
    post(
      route('admin.users.store', {
        onSuccess: () => setOpen(false),
      })
    )
  }

  return (
    <Modal
      opened={open}
      onClose={() => setOpen(false)}
      title={`New user`}
      centered
    >
      <form
        onSubmit={(e) => {
          e.preventDefault()
          handleCreate()
        }}
      >
        <div className='flex flex-col space-y-3'>
          <TextInput
            label='Name'
            name='name'
            placeholder='Eric Wang'
            value={data.name}
            onChange={onHandleChange}
            error={errors.name}
            required
          />
          <TextInput
            label='Email'
            name='email'
            type='email'
            placeholder='eric.w@example.com'
            value={data.email}
            onChange={onHandleChange}
            error={errors.email}
            required
          />
          <TextInput
            label='Password'
            name='password'
            type='password'
            placeholder='Super secure password'
            value={data.password}
            onChange={onHandleChange}
            error={errors.password}
            required
          />
          <Checkbox
            checked={data.root_admin}
            onChange={(e) => setData('root_admin', e.target.checked)}
            label='Administrator'
          />
        </div>
        <Button
          type='submit'
          loading={processing}
          className='mt-3'
          fullWidth
          onClick={() => handleCreate()}
        >
          Create
        </Button>
      </form>
    </Modal>
  )
}

export default NewUserModal
