import { Dialog, Transition } from '@headlessui/react'
import React, { ComponentProps, useRef } from 'react'
import { Fragment, ReactNode } from 'react'
import styled from '@emotion/styled'
import tw from 'twin.macro'
import LoadingDots from '@/components/elements/LoadingDots'

interface Modal extends React.FC<ModalProps> {
  Header: React.FC<{
    children: ReactNode
  }>
  Title: React.FC<{
    children: ReactNode
  }>
  Body: React.FC<{
    children: ReactNode
  }>
  Description: React.FC<{
    children: ReactNode
    bottomMargin?: boolean
  }>
  Actions: React.FC<{
    children: ReactNode
  }>
  Action: React.FC<ComponentProps<'button'> & {
    loading?: boolean
  }>
}

interface ModalProps {
  open: boolean
  onClose: () => void
  children: ReactNode
}

const Modal: Modal = ({ open, onClose, children }) => {
  const ref = useRef(null)

  return (
    <Transition.Root appear={false} show={open} as={Fragment}>
      <Dialog
        as='div'
        initialFocus={ref}
        className='relative z-[3000]'
        onClose={onClose}
      >
        <Transition.Child
          as={Fragment}
          enter='ease-in-out duration-500'
          enterFrom='opacity-0'
          enterTo='opacity-100'
          leave='ease-in-out duration-500'
          leaveFrom='opacity-100'
          leaveTo='opacity-0'
        >
          <div className='fixed inset-0 bg-black bg-opacity-75 transition-opacity' />
        </Transition.Child>

        <div className='fixed inset-0 z-10 overflow-hidden'>
          <div className='flex min-h-full items-end justify-center sm:items-center p-0'>
            <Transition.Child
              as={Fragment}
              enter='ease-out duration-300'
              enterFrom='opacity-0 translate-y-[100vh] sm:-translate-y-[10vh]'
              enterTo='opacity-100 translate-y-0'
              leave='ease-in duration-200'
              leaveFrom='opacity-100 translate-y-0'
              leaveTo='opacity-0 translate-y-[100vh] sm:-translate-y-[10vh]'
            >
              <Dialog.Panel className='absolute sm:w-full sm:max-w-lg bg-background rounded-t-lg sm:rounded-lg overflow-hidden border-t border-x sm:border-b border-accent-200'>
                <input type='hidden' ref={ref} autoFocus />
                {children}
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  )
}

Modal.Header = styled.div`
  ${tw`p-8 sm:p-6 border-b border-accent-200`}
`

Modal.Title = styled.h3`
  ${tw`text-xl font-medium text-foreground text-center`}
`

Modal.Body = styled.div`
${tw`p-6 bg-accent-100`}
`

Modal.Description = ({ children, bottomMargin }) => {
  return (
    <Dialog.Description className={`text-accent-600 text-sm ${bottomMargin && 'mb-5'}`}>
      {children}
    </Dialog.Description>
  )
}

Modal.Actions = styled.div`
  ${tw`flex border-t border-accent-200`}

  & > button:not(:last-child) {
    ${tw`border-r border-accent-200`}
  }

  & > button:is(:last-child) {
    ${tw`!text-foreground`}
  }
`

const StyledAction = styled.button`
${tw`grow py-6 uppercase text-xs text-accent-500 sm:hover:text-foreground active:text-foreground bg-background sm:hover:bg-accent-100 active:bg-accent-100 disabled:bg-accent-100 disabled:cursor-not-allowed transition-colors`}
`

Modal.Action = ({ loading, disabled, children, ...props }) => {
  return <StyledAction {...props} disabled={disabled || loading}>{ loading ? <div className='grid place-items-center w-full h-full'><LoadingDots size={4} /></div> : children }</StyledAction>
}

export default Modal
