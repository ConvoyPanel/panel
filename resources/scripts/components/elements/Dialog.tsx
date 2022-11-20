import Modal from '@/components/elements/Modal'
import styled from '@emotion/styled'
import { ReactNode } from 'react'
import tw from 'twin.macro'

interface Props {
  open: boolean
  onClose: () => void
  onSubmit: () => void
  title: string
  description: string
  children?: ReactNode
  submitText?: string
}

const ModalAction = styled.button`
  ${tw`py-6 uppercase text-xs text-accent-500 sm:hover:text-foreground active:text-foreground bg-background sm:hover:bg-accent-100 active:bg-accent-100 transition-colors`}
`

const Dialog = ({ onSubmit, submitText, children, ...props }: Props) => {
  return (
    <Modal {...props} footer={<footer className='grid grid-cols-2 border-t border-accent-200'>
        <ModalAction className='border-r border-accent-200' onClick={props.onClose}>Cancel</ModalAction>
        <ModalAction className='!text-foreground' onClick={onSubmit}>{submitText || 'Submit'}</ModalAction>
    </footer>}>
      {children}
    </Modal>
  )
}

export default Dialog
