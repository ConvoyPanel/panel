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
                                {/*
                                 * Bounded by the viewport, not by its content: the
                                 * panel is the scroll container's only child and
                                 * the container is `overflow-hidden`, so a panel
                                 * taller than the screen has its head and foot
                                 * clipped with no way to reach them. Capping it
                                 * here and laying it out as a column lets whichever
                                 * section opts into `overflow-y-auto` (Modal.Body)
                                 * absorb the excess, while a modal that already
                                 * fits is untouched.
                                 *
                                 * The `[&>form]` rules extend that column through a
                                 * form. Most modals wrap Modal.Body and
                                 * Modal.Actions in one so the footer can submit, and
                                 * a plain block form is a flex item that refuses to
                                 * shrink below its content -- which left Modal.Body
                                 * with no bounded parent to size `flex-1` against,
                                 * and pushed the submit row out through
                                 * `overflow-hidden` with nothing to scroll it back.
                                 * Making the form a column too puts Modal.Body back
                                 * under the cap. (FormProvider/FormikProvider render
                                 * no DOM, so the form really is a direct child.)
                                 */}
                                <Dialog.Panel
                                    ref={ref}
                                    className='absolute flex max-h-[90dvh] w-full flex-col overflow-hidden bg-background rounded-t-lg sm:max-h-[85vh] sm:max-w-lg sm:rounded-lg border-t border-x sm:border-b border-accent-200 [&>form]:flex [&>form]:min-h-0 [&>form]:flex-1 [&>form]:flex-col'
                                >
                                    {/*
                                     * Where focus lands when the dialog opens. It
                                     * was a `type='hidden'` input, which cannot be
                                     * focused at all -- so `initialFocus` had
                                     * nothing to move focus to, focus stayed on the
                                     * trigger behind the overlay, and the first few
                                     * Tabs walked the page underneath instead of the
                                     * dialog. A `tabIndex={-1}` sentinel is
                                     * focusable programmatically without joining the
                                     * tab order, so it parks focus inside the panel
                                     * without stealing it from the first real field.
                                     */}
                                    <span
                                        ref={focusTrapRef}
                                        tabIndex={-1}
                                        aria-hidden='true'
                                        className='sr-only'
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
