import { Badge, Skeleton } from '@mantine/core'

import { UpgradeItem, useUpgradeCheckSWR } from '@/api/admin/settings/upgrade'

import FormCard from '@/components/elements/FormCard'

import AnnouncementBody from '@/components/admin/announcements/AnnouncementBody'

const EffectBadge = ({ item }: { item: UpgradeItem }) => {
    if (!item.affected) {
        return (
            <Badge color='green' variant='light'>
                Nothing here
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

const UpgradeContainer = () => {
    const { data } = useUpgradeCheckSWR()

    if (!data) {
        return <Skeleton height={500} />
    }

    const affected = data.filter(item => item.affected).length

    return (
        <FormCard>
            <FormCard.Body>
                <FormCard.Title>Upgrade to Convoy 6</FormCard.Title>
                <div className='mt-3 space-y-3 text-sm text-accent-600'>
                    <p>
                        Convoy 6 is a new major version with a new data model.
                        Most of this panel's data moves over as it is; the items
                        below are what its upgrade drops or changes, with what
                        that means for this panel's data. This page only reads:
                        nothing changes until you upgrade.
                    </p>
                    <p>
                        {affected === 0
                            ? 'None of them apply to this panel.'
                            : `${affected} of ${data.length} apply to this panel.`}{' '}
                        The same check runs on the command line with{' '}
                        <code className='rounded bg-accent-200 px-1 py-0.5 font-mono text-xs text-foreground'>
                            php artisan convoy:upgrade-check
                        </code>
                        .
                    </p>
                </div>

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
                            {item.details.length > 0 && (
                                <ul className='mt-2 list-disc space-y-1 pl-5 text-sm text-accent-600'>
                                    {item.details.map(detail => (
                                        <li key={detail}>{detail}</li>
                                    ))}
                                </ul>
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
