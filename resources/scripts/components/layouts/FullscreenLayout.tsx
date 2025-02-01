import { cn } from '@/utils'
import { IconX } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { ReactNode } from 'react'

import { buttonVariants } from '@/components/ui/Button'

interface Props {
    backLink: string
    title: string
    headerActions?: ReactNode
    center?: boolean
    children?: ReactNode
}

const FullscreenLayout = ({
    backLink,
    title,
    headerActions,
    center,
    children,
}: Props) => {
    return (
        <div className={'flex h-full flex-col'}>
            <header
                className={
                    'flex justify-between border-b border-accent py-4 pl-3 pr-5'
                }
            >
                <div className={'flex items-center'}>
                    <Link
                        to={backLink}
                        className={cn(
                            buttonVariants({
                                variant: 'ghost',
                                size: 'icon',
                            }),
                            'absolute'
                        )}
                        aria-label='Go back'
                    >
                        <IconX
                            aria-hidden='true'
                            className={'size-4 text-accent-foreground/60'}
                        />
                    </Link>
                    <div
                        className={'ml-11 mr-5 h-4 w-px bg-muted-foreground/30'}
                    />
                    <h1
                        className={
                            'text-sm font-medium text-accent-foreground/90'
                        }
                    >
                        {title}
                    </h1>
                </div>
                <div className={'flex items-center space-x-2'}>
                    {headerActions}
                </div>
            </header>
            <main
                className={cn(
                    'flex h-full w-full grow flex-col p-4 sm:px-6 sm:py-0',
                    center && 'items-center'
                )}
            >
                {children}
            </main>
        </div>
    )
}

export default FullscreenLayout
