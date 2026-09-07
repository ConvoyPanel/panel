import Toolbar from '@/features/servers/components/client/Overview/Toolbar.tsx'
import { useServer } from '@/features/servers/detail/api.ts'
import useClipboard from '@/hooks/use-clipboard.ts'

import { PageToolbar } from '@/components/ui/PageToolbar'
import { Heading } from '@/components/ui/Typography'

const Header = () => {
    const { data: server } = useServer()
    const { copy } = useClipboard({
        successMessage: 'Copied hostname to clipboard',
    })

    const copyHostname = () => copy(server!.hostname)

    return (
        <>
            <div className={'overflow-hidden'}>
                <Heading className={'mt-2 truncate sm:mt-3'}>
                    {server?.name}
                </Heading>
                <button
                    onClick={copyHostname}
                    className={
                        'text-muted-foreground inline-block max-w-full truncate text-sm sm:mt-2 sm:text-base'
                    }
                    aria-label={`Click to copy hostname: ${server?.hostname}`}
                >
                    {server?.hostname}
                </button>
            </div>
            {/* Its own row under the heading, like every other page's actions.
                Rendered once: sharing the hostname's line meant two Toolbars,
                one per breakpoint, each running the power-action toast hook. */}
            <PageToolbar>
                <Toolbar className={'w-full @sm:flex-wrap'} />
            </PageToolbar>
        </>
    )
}

export default Header
