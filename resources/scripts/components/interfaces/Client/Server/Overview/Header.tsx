import { toast } from '@/hooks/use-toast.ts'
import { cn } from '@/utils'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import Toolbar from '@/components/interfaces/Client/Server/Overview/Toolbar.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'


const Header = () => {
    const { data: server } = useServerSWR()
    const { data: state } = useServerStateSWR()

    const copyHostname = async () => {
        try {
            await navigator.clipboard.writeText(server!.hostname)
            toast({ description: 'Copied hostname to clipboard' })
        } catch {
            toast({
                description: 'Failed to copy hostname to clipboard',
                variant: 'destructive',
            })
        }
    }

    return (
        <div
            className={
                'flex flex-col justify-between gap-6 md:flex-row md:items-end md:gap-2'
            }
        >
            <div className={'overflow-hidden'}>
                {state ? (
                    <p
                        className={
                            'flex items-center text-sm text-muted-foreground'
                        }
                    >
                        <span className='relative mx-2 flex h-2 w-2'>
                            {state.state === 'running' && (
                                <span className='absolute inline-flex h-full w-full animate-ping rounded-full bg-green-600 opacity-75' />
                            )}
                            <span
                                className={cn(
                                    'relative inline-flex h-2 w-2 rounded-full',
                                    state.state === 'running'
                                        ? 'bg-green-600'
                                        : 'bg-destructive'
                                )}
                            />
                        </span>
                        {state.state === 'running' ? 'Running' : 'Stopped'}
                    </p>
                ) : (
                    <Skeleton className={'h-5 w-24'} />
                )}
                <Heading className={'mt-2 truncate sm:mt-3'}>
                    {server?.name}
                </Heading>
                <button
                    onClick={copyHostname}
                    className={
                        'text-sm text-muted-foreground sm:mt-2 sm:text-base'
                    }
                    aria-label={`Click to copy hostname: ${server?.hostname}`}
                >
                    {server?.hostname}
                </button>
            </div>
            <Toolbar />
        </div>
    )
}

export default Header
