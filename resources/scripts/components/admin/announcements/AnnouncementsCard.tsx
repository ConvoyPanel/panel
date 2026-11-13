import {
    InformationCircleIcon,
    MegaphoneIcon,
    ShieldExclamationIcon,
    WrenchScrewdriverIcon,
    XMarkIcon,
} from '@heroicons/react/20/solid'
import { Badge } from '@mantine/core'
import { ComponentType } from 'react'
import { Link } from 'react-router-dom'

import dismissAnnouncement from '@/api/admin/announcements/dismissAnnouncement'
import {
    Announcement,
    AnnouncementSeverity,
    AnnouncementType,
} from '@/api/admin/announcements/getAnnouncements'
import useAnnouncementsSWR from '@/api/admin/announcements/useAnnouncementsSWR'

import Card from '@/components/elements/Card'

import AnnouncementBody from '@/components/admin/announcements/AnnouncementBody'

export const typeIcons: Record<
    AnnouncementType,
    ComponentType<{ className?: string }>
> = {
    security: ShieldExclamationIcon,
    release: MegaphoneIcon,
    maintenance: WrenchScrewdriverIcon,
    info: InformationCircleIcon,
}

export const severityColors: Record<AnnouncementSeverity, string> = {
    critical: 'red',
    high: 'orange',
    medium: 'yellow',
    low: 'gray',
}

export const AnnouncementMeta = ({
    announcement,
}: {
    announcement: Announcement
}) => (
    <div className='flex flex-wrap items-center gap-2'>
        {announcement.severity && (
            <Badge
                color={severityColors[announcement.severity]}
                variant='light'
            >
                {announcement.severity}
            </Badge>
        )}
        <Badge color='gray' variant='outline'>
            {announcement.type}
        </Badge>
        <span className='description-small'>
            {announcement.publishedAt.toLocaleDateString(undefined, {
                dateStyle: 'medium',
            })}
        </span>
    </div>
)

/**
 * Announcements from the Convoy team that apply to this panel and aren't
 * critical (those are a banner on every page). Each admin dismisses them for
 * themselves.
 */
const AnnouncementsCard = ({ className }: { className?: string }) => {
    const { data, mutate } = useAnnouncementsSWR()

    if (!data || data.overview.length === 0) {
        return null
    }

    const dismiss = (id: string) => {
        mutate(
            {
                ...data,
                overview: data.overview.filter(
                    announcement => announcement.id !== id
                ),
            },
            false
        )
        dismissAnnouncement(id).catch(() => mutate())
    }

    return (
        <Card className={className}>
            <div className='flex items-center justify-between gap-3'>
                <div>
                    <h2 className='h5'>Announcements</h2>
                    <p className='description-small mt-1'>
                        From the Convoy team, for this panel's versions.{' '}
                        <Link to='/admin/settings/updates' className='link'>
                            See all
                        </Link>
                    </p>
                </div>
                <MegaphoneIcon className='h-5 w-5 text-accent-400' />
            </div>
            <div className='mt-2'>
                {data.overview.map(announcement => {
                    const Icon = typeIcons[announcement.type]

                    return (
                        <div
                            key={announcement.id}
                            className='flex gap-3 border-b border-accent-200 py-4 last:border-0 last:pb-0'
                        >
                            <Icon className='mt-0.5 h-5 w-5 shrink-0 text-accent-500' />
                            <div className='min-w-0 flex-1'>
                                <AnnouncementMeta announcement={announcement} />
                                <a
                                    href={announcement.url}
                                    target='_blank'
                                    rel='noopener noreferrer'
                                    className='link mt-2 block font-medium text-foreground'
                                >
                                    {announcement.title}
                                </a>
                                <AnnouncementBody
                                    body={announcement.body}
                                    className='description-small mt-1'
                                    linkClassName='link'
                                />
                            </div>
                            <button
                                type='button'
                                aria-label={`Dismiss "${announcement.title}"`}
                                title='Dismiss'
                                className='h-fit rounded p-1 text-accent-500 transition-colors hover:bg-accent-200 hover:text-foreground'
                                onClick={() => dismiss(announcement.id)}
                            >
                                <XMarkIcon className='h-5 w-5' />
                            </button>
                        </div>
                    )
                })}
            </div>
        </Card>
    )
}

export default AnnouncementsCard
