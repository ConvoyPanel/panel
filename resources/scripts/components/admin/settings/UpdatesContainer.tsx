import { useFlashKey } from '@/util/useFlash'
import {
    ArrowTopRightOnSquareIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    MinusCircleIcon,
} from '@heroicons/react/20/solid'
import { Badge, Skeleton } from '@mantine/core'
import { useState } from 'react'

import {
    UpdatesAnnouncement,
    checkForUpdates,
    useUpdatesSWR,
} from '@/api/admin/settings/updates'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'

import AnnouncementBody from '@/components/admin/announcements/AnnouncementBody'
import { AnnouncementMeta } from '@/components/admin/announcements/AnnouncementsCard'

const formatDate = (date: Date) =>
    date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })

const Row = ({ label, children }: { label: string; children: any }) => (
    <div className='flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b border-accent-200 py-3 last:border-0'>
        <dt className='dt'>{label}</dt>
        <dd className='text-sm text-foreground'>{children}</dd>
    </div>
)

const AnnouncementRow = ({
    announcement,
}: {
    announcement: UpdatesAnnouncement
}) => {
    const urgent =
        announcement.severity === 'critical' || announcement.severity === 'high'

    return (
        <div className='border-b border-accent-200 py-5 first:pt-0 last:border-0 last:pb-0'>
            <div className='flex flex-wrap items-center justify-between gap-2'>
                <AnnouncementMeta announcement={announcement} />
                {announcement.applies ? (
                    <Badge color={urgent ? 'red' : 'blue'} variant='filled'>
                        Applies to this panel
                    </Badge>
                ) : (
                    <Badge color='gray' variant='light'>
                        Doesn't apply
                    </Badge>
                )}
            </div>
            <a
                href={announcement.url}
                target='_blank'
                rel='noopener noreferrer'
                className='link mt-2 inline-flex items-center gap-1 font-medium text-foreground'
            >
                {announcement.title}
                <ArrowTopRightOnSquareIcon className='h-4 w-4' />
            </a>
            <AnnouncementBody
                body={announcement.body}
                className='description-small mt-1 !text-foreground'
                linkClassName='link'
            />
            <ul className='mt-3 space-y-1'>
                {announcement.reasons.map(reason => (
                    <li
                        key={reason.text}
                        className='flex gap-2 text-sm text-accent-600'
                    >
                        {!reason.matches ? (
                            <CheckCircleIcon className='mt-0.5 h-4 w-4 shrink-0 text-success' />
                        ) : announcement.applies && urgent ? (
                            <ExclamationCircleIcon className='mt-0.5 h-4 w-4 shrink-0 text-error' />
                        ) : (
                            <MinusCircleIcon className='mt-0.5 h-4 w-4 shrink-0 text-accent-400' />
                        )}
                        <span>{reason.text}</span>
                    </li>
                ))}
                {announcement.dismissed && (
                    <li className='description-small'>
                        You dismissed it from the overview.
                    </li>
                )}
            </ul>
        </div>
    )
}

const UpdatesContainer = () => {
    const { data, mutate } = useUpdatesSWR()
    const [checking, setChecking] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin:settings:updates'
    )

    const check = async () => {
        clearFlashes()
        setChecking(true)

        try {
            await mutate(await checkForUpdates(), false)
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }

        setChecking(false)
    }

    if (!data) {
        return (
            <>
                <Skeleton height={220} />
                <Skeleton height={320} />
            </>
        )
    }

    const { panel, release, feed, announcements } = data

    return (
        <>
            <FlashMessageRender byKey='admin:settings:updates' />
            <FormCard>
                <FormCard.Body>
                    <FormCard.Title>Version</FormCard.Title>
                    <dl className='mt-3'>
                        <Row label='Convoy'>
                            {panel.fromSource
                                ? `${panel.version} (built from source)`
                                : panel.version}
                        </Row>
                        <Row label='PHP'>{panel.php}</Row>
                        <Row label='Latest release'>
                            {!release.enabled ? (
                                'Not checked: CONVOY_RELEASES_URL is empty'
                            ) : !release.latest ? (
                                'Not checked yet'
                            ) : (
                                <span className='inline-flex flex-wrap items-center gap-2'>
                                    <a
                                        href={release.latest.url}
                                        target='_blank'
                                        rel='noopener noreferrer'
                                        className='link'
                                    >
                                        {release.latest.version}
                                    </a>
                                    {release.updateAvailable === true && (
                                        <Badge color='orange' variant='light'>
                                            Update available
                                        </Badge>
                                    )}
                                    {release.updateAvailable === false && (
                                        <Badge color='green' variant='light'>
                                            Up to date
                                        </Badge>
                                    )}
                                </span>
                            )}
                        </Row>
                    </dl>
                </FormCard.Body>
            </FormCard>

            <FormCard>
                <FormCard.Body>
                    <FormCard.Title>Announcements</FormCard.Title>
                    <p className='description-small mt-1'>
                        Security notices and news from the Convoy team, from a
                        signed feed the panel checks every hour. The panel sends
                        nothing about itself to get them. Each one is listed
                        with the reason it does or doesn't apply here.
                    </p>
                    <div className='mt-3 space-y-1 text-sm'>
                        {!feed.enabled ? (
                            <p className='text-foreground'>
                                The check is off: CONVOY_ANNOUNCEMENTS_URL is
                                empty.
                            </p>
                        ) : feed.checkedAt ? (
                            <p className='text-accent-600'>
                                Last checked {formatDate(feed.checkedAt)}.
                            </p>
                        ) : (
                            <p className='text-accent-600'>Not checked yet.</p>
                        )}
                        {feed.error && (
                            <p className='text-error'>
                                {feed.error.message} (
                                {formatDate(feed.error.at)}
                                ). The last verified copy is shown.
                            </p>
                        )}
                    </div>
                    <div className='mt-6'>
                        {announcements.length === 0 ? (
                            <p className='description-small py-4 text-center'>
                                There are no current announcements.
                            </p>
                        ) : (
                            announcements.map(announcement => (
                                <AnnouncementRow
                                    key={announcement.id}
                                    announcement={announcement}
                                />
                            ))
                        )}
                    </div>
                </FormCard.Body>
                <FormCard.Footer>
                    <Button
                        size='sm'
                        variant='filled'
                        loading={checking}
                        disabled={!feed.enabled && !release.enabled}
                        onClick={check}
                    >
                        Check now
                    </Button>
                </FormCard.Footer>
            </FormCard>
        </>
    )
}

export default UpdatesContainer
