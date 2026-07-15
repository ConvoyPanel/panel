import { IconSearch } from '@tabler/icons-react'
import { Suspense, lazy, useEffect, useState } from 'react'

import { Button } from '@/components/ui/Button'
import type { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

const NavSearchDialog = lazy(() => import('./NavSearchDialog.tsx'))

interface Props {
    nav: SidebarNav
}

const isEditableTarget = (target: EventTarget | null) => {
    if (!(target instanceof HTMLElement)) {
        return false
    }

    return (
        target.isContentEditable ||
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT'
    )
}

const NavSearch = ({ nav }: Props) => {
    const [open, setOpen] = useState(false)
    const [activated, setActivated] = useState(false)

    const openSearch = () => {
        setActivated(true)
        setOpen(true)
    }

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            const isCommandK =
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            const isSlash = event.key === '/' && !isEditableTarget(event.target)

            if (!isCommandK && !isSlash) {
                return
            }

            event.preventDefault()
            setActivated(true)
            setOpen(value => !value)
        }

        document.addEventListener('keydown', onKeyDown)

        return () => document.removeEventListener('keydown', onKeyDown)
    }, [])

    return (
        <>
            <Button
                type='button'
                variant='outline'
                onClick={openSearch}
                className='text-muted-foreground hidden h-9 w-44 justify-between px-3 md:inline-flex lg:w-56'
            >
                <span className='flex min-w-0 items-center gap-2'>
                    <IconSearch className='h-4 w-4 shrink-0' />
                    <span className='truncate'>Search</span>
                </span>
                <kbd className='bg-muted text-muted-foreground rounded border px-1.5 py-0.5 text-[0.65rem] leading-none font-medium'>
                    Ctrl K
                </kbd>
            </Button>

            {activated ? (
                <Suspense fallback={null}>
                    <NavSearchDialog
                        nav={nav}
                        open={open}
                        onOpenChange={setOpen}
                    />
                </Suspense>
            ) : null}
        </>
    )
}

export default NavSearch
