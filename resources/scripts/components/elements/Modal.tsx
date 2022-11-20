import { Dialog, Transition } from '@headlessui/react'
import { useRef } from 'react'
import { Fragment, ReactNode } from 'react'

interface Props {
    open: boolean
    onClose: () => void
    title: string
    description: string
    children?: ReactNode
    footer?: ReactNode
}

const Modal = ({ open, onClose, title, description, children, footer}: Props) => {
  const ref = useRef(null)

  return (
    <Transition.Root appear={false} show={open} as={Fragment}>
      <Dialog as='div' initialFocus={ref} className='relative z-[3000]' onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-in-out duration-500"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in-out duration-500"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-black bg-opacity-75 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-10 overflow-hidden">
          <div className="flex min-h-full items-end justify-center sm:items-center p-0">
          <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 translate-y-[100vh] sm:-translate-y-[10vh]"
              enterTo="opacity-100 translate-y-0"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 translate-y-0"
              leaveTo="opacity-0 translate-y-[100vh] sm:-translate-y-[10vh]"
            >
              <Dialog.Panel className='absolute sm:w-full sm:max-w-lg bg-background rounded-t-lg sm:rounded-lg overflow-hidden border-t border-x sm:border-b border-accent-200'>
                <Dialog.Title className='p-8 sm:p-6 text-xl font-medium border-b border-accent-200 text-foreground text-center'>
                  { title }
                </Dialog.Title>
                <div className='p-6 bg-accent-100'>
                  <Dialog.Description className={`text-accent-600 text-sm ${children && 'mb-5'}`}>
                    { description }
                  </Dialog.Description>
                  <input type="hidden" ref={ref} autoFocus />
                  { children }
                </div>
                { footer }
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  )
}

export default Modal
