import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import { Button } from '@/components/ui/Button'


const Toolbar = () => {
    const { data } = useServerStateSWR()

    console.log(data)

    return (
        <div
            className={
                'grid grid-cols-2 items-center justify-end gap-2 sm:flex'
            }
        >
            <Button
                variant={'outline'}
                disabled={!data || data?.state === 'running'}
            >
                Start
            </Button>
            <Button
                variant={'outline'}
                disabled={!data || data?.state === 'stopped'}
            >
                Restart
            </Button>
            <Button
                variant={'destructiveOutline'}
                disabled={!data || data?.state === 'stopped'}
            >
                Kill
            </Button>
            <Button
                variant={'destructive'}
                disabled={!data || data?.state === 'stopped'}
            >
                Shutdown
            </Button>
        </div>
    )
}

export default Toolbar
