import {
    ArrowRightIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/20/solid'
import { Alert, Badge, Skeleton } from '@mantine/core'

import {
    AddressPoolSplit,
    UpgradeItem,
    useUpgradeCheckSWR,
} from '@/api/admin/settings/upgrade'

import FormCard from '@/components/elements/FormCard'

import AnnouncementBody from '@/components/admin/announcements/AnnouncementBody'

const EffectBadge = ({ item }: { item: UpgradeItem }) => {
    if (!item.affected) {
        return (
            <Badge color='green' variant='light'>
                Doesn't Apply
            </Badge>
        )
    }

    return item.effect === 'dropped' ? (
        <Badge color='red' variant='light'>
            Dropped
        </Badge>
    ) : (
        <Badge color='orange' variant='light'>
            Changed
        </Badge>
    )
}

const codeClass =
    'whitespace-nowrap rounded bg-accent-200 px-1 py-0.5 font-mono text-xs text-foreground'

const SHOWN_BLOCKS = 5

const PoolSplit = ({ split }: { split: AddressPoolSplit }) => {
    const hidden = split.blocks.length - SHOWN_BLOCKS

    return (
        <div className='overflow-hidden rounded border border-accent-200 text-sm'>
            <div className='flex flex-wrap items-center gap-2 bg-accent-100 px-3 py-2'>
                <span className='font-medium text-foreground'>
                    {split.pool}
                </span>
                <ArrowRightIcon className='h-4 w-4 text-accent-500' />
                <span className='text-accent-600'>
                    {split.blocks.length} blocks
                </span>
            </div>
            <table className='w-full'>
                <thead>
                    <tr className='text-left text-xs text-accent-500'>
                        <th className='px-3 pt-2 font-medium'>MAC Address</th>
                        <th className='px-3 pt-2 text-right font-medium'>
                            Addresses
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {split.blocks.slice(0, SHOWN_BLOCKS).map(block => (
                        <tr key={block.mac ?? ''} className='last:[&>td]:pb-2'>
                            <td className='px-3 py-1'>
                                {block.mac ? (
                                    <span className='font-mono text-foreground'>
                                        {block.mac}
                                    </span>
                                ) : (
                                    <span className='text-accent-500'>
                                        None
                                    </span>
                                )}
                            </td>
                            <td className='px-3 py-1 text-right tabular-nums text-foreground'>
                                {block.addresses}
                            </td>
                        </tr>
                    ))}
                    {hidden > 0 && (
                        <tr>
                            <td
                                colSpan={2}
                                className='px-3 pb-2 pt-1 text-accent-500'
                            >
                                {hidden} more
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    )
}

const UpgradeContainer = () => {
    const { data } = useUpgradeCheckSWR()

    if (!data) {
        return <Skeleton height={500} />
    }

    const affected = data.filter(item => item.affected).length

    return (
        <FormCard>
            <FormCard.Body>
                <FormCard.Title>Convoy 6 Upgrade Checklist</FormCard.Title>
                <Alert
                    color='red'
                    variant='light'
                    icon={<ExclamationTriangleIcon className='h-5 w-5' />}
                    className='mt-4'
                >
                    <p className='text-sm text-foreground'>
                        <span className='font-medium'>
                            Upgrading to Convoy 6 permanently drops or changes
                            the data below.
                        </span>{' '}
                        Read each item, and finish anything under What to do
                        before you upgrade. Back up the database first with{' '}
                        <code className={codeClass}>convoy backup</code>.
                    </p>
                </Alert>
                <p className='mt-4 text-sm text-accent-600'>
                    {affected === 0
                        ? 'None of these apply to this panel.'
                        : `${affected} of ${data.length} apply to this panel.`}{' '}
                    To see this checklist from the command line, use{' '}
                    <code className={codeClass}>
                        convoy artisan convoy:upgrade-check
                    </code>
                    .
                </p>

                <div className='mt-6'>
                    {data.map(item => (
                        <div
                            key={item.key}
                            className='border-t border-accent-200 py-5 last:pb-0'
                        >
                            <div className='flex flex-wrap items-center justify-between gap-2'>
                                <h3 className='font-medium text-foreground'>
                                    {item.title}
                                </h3>
                                <EffectBadge item={item} />
                            </div>
                            <AnnouncementBody
                                body={item.summary}
                                className={`mt-2 text-sm ${
                                    item.affected
                                        ? 'text-foreground'
                                        : 'text-accent-500'
                                }`}
                            />
                            {item.splits.length > 0 && (
                                <div className='mt-3 space-y-3'>
                                    {item.splits.map(split => (
                                        <PoolSplit
                                            key={split.pool}
                                            split={split}
                                        />
                                    ))}
                                </div>
                            )}
                            {item.action && (
                                <div className='mt-3 rounded border border-accent-200 bg-accent-100 px-3 py-2 text-sm text-foreground'>
                                    <span className='font-medium'>
                                        What to do:{' '}
                                    </span>
                                    <AnnouncementBody
                                        body={item.action}
                                        className='inline [&>p]:inline'
                                    />
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

export default UpgradeContainer
