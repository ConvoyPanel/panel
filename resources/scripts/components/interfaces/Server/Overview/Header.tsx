import { toast } from '@/hooks/use-toast.ts'
import { IconPointFilled } from '@tabler/icons-react'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import Toolbar from '@/components/interfaces/Server/Overview/Toolbar.tsx'

import { Heading } from '@/components/ui/Typography'


const Header = () => {
    const { data: server } = useServerSWR()

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
                <p
                    className={
                        'flex items-center text-sm text-muted-foreground'
                    }
                >
                    <IconPointFilled
                        className={'mr-1.5 h-4 w-4 text-green-500'}
                    />{' '}
                    Running
                </p>
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
