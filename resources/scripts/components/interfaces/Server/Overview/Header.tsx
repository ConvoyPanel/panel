import { toast } from '@/hooks/use-toast.ts'

import useServerSWR from '@/api/servers/use-server-swr.ts'

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
        <div>
            <Heading className={'max-w-md truncate'}>{server?.name}</Heading>
            <button
                onClick={copyHostname}
                className={'mt-2 text-muted-foreground'}
                aria-label={`Click to copy hostname: ${server?.hostname}`}
            >
                {server?.hostname}
            </button>
        </div>
    )
}

export default Header
