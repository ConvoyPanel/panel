// @ts-nocheck
import { Dialog, Transition } from '@headlessui/react'
import { Fragment, ReactNode, forwardRef, useRef } from 'react'

interface Props {
    open: boolean
    onClose: () => void
    children: ReactNode
}

const Drawer = forwardRef<HTMLDivElement, Props>(
    ({ open, onClose, children }, ref) => {
        const focusTrapRef = useRef(null)

        return (
            <Transition.Root appear={false} show={open} as={Fragment}>
                <Dialog
                    as='div'
                    initialFocus={focusTrapRef}
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
                                <Dialog.Panel
                                    ref={ref}
                                    className='absolute w-full sm:max-w-lg bg-background rounded-t-lg sm:rounded-lg border-t border-x sm:border-b border-accent-200'
                                >
                                    <input
                                        type='hidden'
                                        ref={focusTrapRef}
                                        autoFocus
                                    />
                                    {children}
                                </Dialog.Panel>
                            </Transition.Child>
                        </div>
                    </div>
                </Dialog>
            </Transition.Root>
        )
    }
)

export default Drawer
