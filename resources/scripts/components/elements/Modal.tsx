import { Dialog, Transition } from '@headlessui/react'
import { Fragment, ReactNode } from 'react'

interface Props {
    open: boolean
    onClose: () => void
    title: string
    description: string
    children?: ReactNode
}

const Modal = ({ open, onClose, title, description, children}: Props) => {
  return (
    <Transition appear show={open} as={Fragment}>
      <Dialog as='div' className='relative z-[3000]' onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter='ease-out duration-300'
          enterFrom='opacity-0'
          enterTo='opacity-100'
          leave='ease-in duration-200'
          leaveFrom='opacity-100'
          leaveTo='opacity-0'
        >
          <div className='fixed inset-0 bg-black bg-opacity-25' />
        </Transition.Child>
        <div className='fixed inset-0 overflow-y-hidden'>
          <div className='flex min-h-full items-end md:items-center justify-center'>
            <Transition.Child
          as={Fragment}
              enter='modal ease-in-out duration-[400ms] md:duration-200'
              enterFrom='enter-from opacity-0 md:scale-95'
              enterTo='enter-to opacity-100 md:scale-100'
              leave='modal ease-in-out duration-[400ms] md:duration-200'
              leaveFrom='leave-from opacity-100 md:scale-100'
              leaveTo='leave-to opacity-0 md:scale-95'
            >
              <Dialog.Panel className='w-full md:max-w-[500px] bg-background rounded-t-lg md:rounded-lg overflow-hidden'>
                <Dialog.Title className='p-8 md:p-6 text-xl font-medium border-b border-accent-200 text-foreground text-center'>
                  { title }
                </Dialog.Title>
                <div className='p-6 bg-accent-100'>
                  <Dialog.Description className={`text-accent-600 text-sm ${children && 'mb-5'}`}>
                    { description }
                  </Dialog.Description>
                  { children }
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  )
}

export default Modal
