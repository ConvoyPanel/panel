import { ServerContext } from '@/pages/servers/Show'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const BasicSettings = () => {
  const serverContext = useContext(ServerContext)

  const { data, setData, post, processing, errors, reset } = useForm({
    '_method': 'patch',
    name: serverContext?.server.name,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()

    post(route('servers.show.settings.update-basic-info', serverContext?.server.id))
  }

  useEffect(() => {
    if (!processing) {
        Inertia.reload({ only: ['server']})
    }
  }, [processing])

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => {
    setData(
      // @ts-ignore
      event.target.name,
      event.target.type === 'checkbox'
        ? event.target.checked
        : event.target.value
    )
  }

  return (
    <Paper shadow='xs' className='p-card w-full'>
      <h3 className='h3'>Basic Settings</h3>

      <form onSubmit={submit}>
        <TextInput
          label='Name'
          name='name'
          value={data.name}
          className='mt-1 block w-full'
          onChange={onHandleChange}
          required
        />

        <div className='flex items-center justify-end mt-4'>
          <Button type='submit' className='ml-4' loading={processing}>
            Save
          </Button>
        </div>
      </form>
    </Paper>
  )
}

export default BasicSettings
