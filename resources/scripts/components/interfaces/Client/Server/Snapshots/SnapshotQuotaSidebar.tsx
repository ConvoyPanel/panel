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
                <span className={'block text-2xl font-bold'}>3 snapshots</span>
                <span className={'text-muted-foreground'}>
                    out of 10 snapshots
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

const SnapshotQuotaSidebar = () => {
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
                            <SheetTitle>Snapshot Quota</SheetTitle>
                        </SheetHeader>
                        <dl className={'mt-4 flex flex-col gap-10'}>
                            {quotas.map(quota => (
                                <div key={quota.label}>
                                    <dt className={'text-sm font-medium'}>
                                        {quota.label}
                                    </dt>
                                    <dd className={'mt-2 mb-4'}>
                                        {quota.value}
                                    </dd>
                                    {quota.footer}
                                </div>
                            ))}
                        </dl>
                    </SheetContent>
                </Sheet>
            </div>
        </>
    )
}

export default SnapshotQuotaSidebar
