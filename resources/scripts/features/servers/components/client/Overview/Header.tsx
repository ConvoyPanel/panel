import { toast } from 'sonner'

import { useServer } from '@/features/servers/detail/api.ts'

import Toolbar from '@/features/servers/components/client/Overview/Toolbar.tsx'

import { Heading } from '@/components/ui/Typography'

const Header = () => {
    const { data: server } = useServer()

    const copyHostname = async () => {
        try {
            await navigator.clipboard.writeText(server!.hostname)
            toast.success('Copied hostname to clipboard')
        } catch {
            toast.error('Failed to copy hostname to clipboard')
        }
    }

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
