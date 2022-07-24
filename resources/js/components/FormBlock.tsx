import { Button, Paper, TextInput } from '@mantine/core'
import { FormEvent, ReactNode } from 'react'

interface Props {
  title: string
  inputs: ReactNode
  actions?: ReactNode
  defaultAction?: boolean
  processing?: boolean
  onSubmit: (e: FormEvent<HTMLFormElement>) => void
}

const FormBlock = ({
  title,
  inputs,
  actions,
  defaultAction,
  processing,
  onSubmit,
}: Props) => {
  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()

    onSubmit(e)
  }

  return (
      <Paper shadow='xs' className='!flex flex-col p-card w-full'>
        <h3 className='h3 grow-0'>{title}</h3>

        <form className='relative flex flex-col justify-between grow' onSubmit={submit}>
          <div>{inputs}</div>
          <div className='flex items-center justify-end mt-4 bottom-0'>
            {defaultAction && (
              <Button type='submit' className='ml-4' loading={processing}>
                Save
              </Button>
            )}
            {actions}
          </div>
        </form>
      </Paper>
  )
}

export default FormBlock
