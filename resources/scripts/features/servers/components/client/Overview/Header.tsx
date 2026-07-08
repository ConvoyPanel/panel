import { useServer } from '@/features/servers/detail/api.ts'
import useClipboard from '@/hooks/use-clipboard.ts'

import Toolbar from '@/features/servers/components/client/Overview/Toolbar.tsx'

import { Heading } from '@/components/ui/Typography'

const Header = () => {
    const { data: server } = useServer()
    const { copy } = useClipboard({
        successMessage: 'Copied hostname to clipboard',
    })

    const copyHostname = () => copy(server!.hostname)

    return (
        <div className={'flex flex-col gap-6'}>
            <div className={'overflow-hidden'}>
                <Heading className={'mt-2 truncate sm:mt-3'}>
                    {server?.name}
                </Heading>
                <div className={'flex justify-between sm:mt-2 '}>
                    <button
                        onClick={copyHostname}
                        className={'text-sm text-muted-foreground sm:text-base'}
                        aria-label={`Click to copy hostname: ${server?.hostname}`}
                    >
                        {server?.hostname}
                    </button>
                    <Toolbar className={'hidden @sm:flex'} />
                </div>
            </div>
            <Toolbar className={'grid @sm:hidden'} />
        </div>
    )
}

export default Header
