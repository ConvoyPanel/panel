import ConsoleView from '@/features/servers/console/ConsoleView'
import { useServer } from '@/features/servers/detail/api.ts'
import { IconArrowLeft } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'

import { buttonVariants } from '@/components/ui/Button'

export const Route = createLazyFileRoute(
    '/_app/(fullscreen)/servers/$serverUuid/console'
)({ component: ConsolePage })

function ConsolePage() {
    const { serverUuid } = Route.useParams()
    const { type } = Route.useSearch()
    const { data: server } = useServer(serverUuid)
    return (
        <div className='flex h-dvh min-h-0 flex-col bg-zinc-950 text-zinc-100'>
            <header className='flex h-12 shrink-0 items-center gap-3 border-b border-white/10 px-3'>
                <Link
                    to='/servers/$serverUuid'
                    params={{ serverUuid }}
                    aria-label='Back to server'
                    className={buttonVariants({
                        variant: 'ghost',
                        size: 'icon',
                        className:
                            'text-zinc-300 hover:bg-zinc-900 hover:text-white',
                    })}
                >
                    <IconArrowLeft />
                </Link>
                <div className='min-w-0'>
                    <p className='truncate text-sm font-medium'>
                        {server?.name ?? 'Console'}
                    </p>
                    <p className='text-xs text-zinc-400 capitalize'>
                        {type === 'novnc' ? 'Display' : 'Terminal'}
                    </p>
                </div>
            </header>
            <main className='min-h-0 flex-1'>
                <ConsoleView serverUuid={serverUuid} type={type} />
            </main>
        </div>
    )
}
