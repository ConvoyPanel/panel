import useServer from '@/api/servers/use-server.ts'

import { Button } from '@/components/ui/Button'
import { LinearProgressBar, RingProgress } from '@/components/ui/Progress'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/Sheet'

const BackupQuotaSidebar = () => {
    const { data: _server } = useServer()

    return (
        <>
            <div className={'flex justify-end'}>
                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant={'outline'} className={'gap-3'}>
                            <RingProgress
                                thickness={'xl'}
                                className={'h-6 w-6'}
                                value={30}
                            />
                            <RingProgress
                                thickness={'xl'}
                                className={'h-6 w-6'}
                                value={98}
                            />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side={'right'}>
                        <SheetHeader>
                            <SheetTitle>Backup Quota</SheetTitle>
                        </SheetHeader>
                        <dl className={'mt-4 flex flex-col gap-10'}>
                            <div>
                                <dt className={'text-sm font-medium'}>Count</dt>
                                <dd className={'mb-4 mt-2'}>
                                    <span
                                        className={
                                            'block text-2xl font-bold text-foreground'
                                        }
                                    >
                                        3 backups
                                    </span>
                                    out of 10 backups
                                </dd>
                                <LinearProgressBar value={30} />
                            </div>
                            <div>
                                <dt className={'text-sm font-medium'}>
                                    Storage Usage
                                </dt>
                                <dd className={'mb-4 mt-2'}>
                                    <span
                                        className={
                                            'block text-2xl font-bold text-foreground'
                                        }
                                    >
                                        24 GiB used
                                    </span>
                                    out of 24.5 GiB
                                </dd>
                                <LinearProgressBar value={30} />
                            </div>
                        </dl>
                    </SheetContent>
                </Sheet>
            </div>
        </>
    )
}

export default BackupQuotaSidebar
