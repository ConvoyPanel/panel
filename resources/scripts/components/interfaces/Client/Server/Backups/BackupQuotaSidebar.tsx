import useServerSWR from '@/api/servers/use-server-swr.ts'

import styles from '@/components/interfaces/Client/Server/Snapshots/QuotaSidebar.module.css'

import { Button } from '@/components/ui/Button'
import { LinearProgressBar, RingProgress } from '@/components/ui/Progress'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/Sheet'


const quotas = [
    {
        label: 'Count',
        value: (
            <>
                <span className={'block text-2xl font-bold'}>3 backups</span>
                <span className={'text-muted-foreground'}>
                    out of 10 backups
                </span>
            </>
        ),
        footer: <LinearProgressBar value={30} />,
    },
    {
        label: 'Storage Usage',
        value: (
            <>
                <span className={'block text-2xl font-bold'}>24 GiB used</span>
                <span className={'text-muted-foreground'}>out of 24.5 GiB</span>
            </>
        ),
        footer: <LinearProgressBar value={98} />,
    },
]

const BackupQuotaSidebar = () => {
    const { data: server } = useServerSWR()

    console.log({ styles })

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
                                <dt className={styles.quotaSidebar}>Count</dt>
                                <dd className={styles.quotaValue}>
                                    <span className={styles.quotaEmphasized}>
                                        3 snapshots
                                    </span>
                                    out of 10 snapshots
                                </dd>
                            </div>
                        </dl>
                    </SheetContent>
                </Sheet>
            </div>
        </>
    )
}

export default BackupQuotaSidebar
