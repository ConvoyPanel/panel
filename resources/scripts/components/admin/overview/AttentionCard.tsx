import {
    ArrowRightIcon,
    CheckCircleIcon,
    ChevronDownIcon,
    ExclamationTriangleIcon,
    PauseCircleIcon,
} from '@heroicons/react/24/outline'
import { Collapse } from '@mantine/core'
import { ComponentType, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import {
    AttentionSubject,
    DashboardOverview,
} from '@/api/admin/overview/getOverview'

import Card from '@/components/elements/Card'

interface IconProps {
    className?: string
}

type Tone = 'error' | 'warning'

interface AttentionGroup {
    key: string
    icon: ComponentType<IconProps>
    tone: Tone
    /** Summary line used when the group holds more than one record. */
    title: string
    actionLabel: string
    /** The records themselves, capped by the endpoint. */
    subjects: AttentionSubject[]
    /** Destination for one record. */
    to: (subject: AttentionSubject) => string
    /** Real total, which can exceed what `subjects` carries. */
    total: number
    /** Where the leftovers live when the list is capped. */
    overflowTo: string
}

const toneClasses: Record<Tone, string> = {
    error: 'text-error border-error-light bg-error-lighter',
    warning: 'text-warning-dark border-warning bg-warning-lighter',
}

const SubjectRow = ({
    subject,
    to,
}: {
    subject: AttentionSubject
    to: string
}) => (
    <Link
        to={to}
        className='block rounded px-2 py-1.5 transition-colors hover:bg-accent-100'
    >
        <p className='truncate text-sm font-medium text-foreground'>
            {subject.label}
        </p>
        {subject.detail && (
            <p className='description-small truncate'>{subject.detail}</p>
        )}
    </Link>
)

/**
 * The names behind a collapsed group, so the summary line carries something the
 * count does not. Truncation is the browser's job -- the string names as many as
 * it has and says how many it left out.
 */
const preview = (group: AttentionGroup): string => {
    const names = group.subjects.map(subject => subject.label)
    const hidden = group.total - names.length

    return hidden > 0 ? `${names.join(', ')} and ${hidden} more` : names.join(', ')
}

/**
 * One group. A single record is named outright and links straight at itself --
 * "1 server failed to install" plus a hunt through the server list is strictly
 * less information than naming the server. Several become a disclosure over
 * direct links, because the count alone is what made the old card useless.
 */
const GroupRow = ({ group }: { group: AttentionGroup }) => {
    const [open, setOpen] = useState(false)
    const { t } = useTranslation('admin.overview')
    const Icon = group.icon
    const truncated = group.total > group.subjects.length

    const icon = (
        <div className={`shrink-0 rounded-md border p-2 ${toneClasses[group.tone]}`}>
            <Icon className='h-4 w-4' />
        </div>
    )

    if (group.subjects.length === 1) {
        const [subject] = group.subjects

        return (
            <Link
                to={group.to(subject)}
                className='flex items-center gap-3 rounded border border-transparent px-2 py-3 transition-colors hover:border-accent-200 hover:bg-accent-100'
            >
                {icon}
                <div className='min-w-0 flex-1'>
                    <p className='truncate text-sm font-medium text-foreground'>
                        {subject.label}
                    </p>
                    <p className='description-small truncate'>
                        {subject.detail}
                    </p>
                </div>
                <span className='link flex shrink-0 items-center gap-1 text-sm'>
                    {group.actionLabel}
                    <ArrowRightIcon className='h-4 w-4' />
                </span>
            </Link>
        )
    }

    return (
        <div className='rounded border border-transparent'>
            <button
                type='button'
                onClick={() => setOpen(value => !value)}
                aria-expanded={open}
                className='flex w-full items-center gap-3 rounded bg-transparent px-2 py-3 text-left transition-colors hover:bg-accent-100'
            >
                {icon}
                <div className='min-w-0 flex-1'>
                    <p className='truncate text-sm font-medium text-foreground'>
                        {group.title}
                    </p>
                    <p className='description-small truncate'>
                        {preview(group)}
                    </p>
                </div>
                <span className='link flex shrink-0 items-center gap-1 text-sm'>
                    {group.actionLabel}
                    <ChevronDownIcon
                        className={`h-4 w-4 transition-transform ${
                            open ? 'rotate-180' : ''
                        }`}
                    />
                </span>
            </button>
            <Collapse in={open}>
                <div className='ml-2 border-l border-accent-200 pl-3'>
                    {group.subjects.map(subject => (
                        <SubjectRow
                            key={subject.id + subject.label}
                            subject={subject}
                            to={group.to(subject)}
                        />
                    ))}
                    {truncated && (
                        <Link
                            to={group.overflowTo}
                            className='link mx-2 my-1.5 inline-block text-sm'
                        >
                            {t('attention_more', {
                                count: group.total - group.subjects.length,
                            })}
                        </Link>
                    )}
                </div>
            </Collapse>
        </div>
    )
}

const AttentionCard = ({ data }: { data: DashboardOverview }) => {
    const { t } = useTranslation('admin.overview')
    const { attention, summary, backups, servers } = data

    const groups: AttentionGroup[] = []

    if (attention.failedServers.length > 0) {
        groups.push({
            key: 'failed-servers',
            icon: ExclamationTriangleIcon,
            tone: 'error',
            title: t('attention_failed_servers', {
                count: summary.failedServers,
            }),
            actionLabel: t('attention_fix'),
            subjects: attention.failedServers,
            to: subject => `/admin/servers/${subject.id}`,
            total: summary.failedServers,
            overflowTo: '/admin/servers',
        })
    }

    if (attention.failedBackups.length > 0) {
        groups.push({
            key: 'failed-backups',
            icon: ExclamationTriangleIcon,
            tone: 'error',
            title: t('attention_failed_backups', { count: backups.failed }),
            actionLabel: t('attention_view'),
            // The backups tab is the only page that shows a backup, and it is
            // keyed by the server's short uuid rather than its id.
            to: subject => `/servers/${subject.id}/backups`,
            subjects: attention.failedBackups,
            total: backups.failed,
            overflowTo: '/admin/servers',
        })
    }

    if (attention.suspendedServers.length > 0) {
        groups.push({
            key: 'suspended-servers',
            icon: PauseCircleIcon,
            tone: 'warning',
            title: t('attention_suspended_servers', {
                count: servers.suspended,
            }),
            actionLabel: t('attention_view'),
            subjects: attention.suspendedServers,
            to: subject => `/admin/servers/${subject.id}`,
            total: servers.suspended,
            overflowTo: '/admin/servers',
        })
    }

    return (
        <Card className='col-span-12 flex flex-col lg:col-span-5'>
            <div className='flex items-center justify-between gap-3'>
                <div>
                    <h2 className='h5'>{t('attention')}</h2>
                    <p className='description-small mt-1'>
                        {t('attention_description')}
                    </p>
                </div>
                <ExclamationTriangleIcon
                    className={`h-5 w-5 ${
                        groups.length > 0 ? 'text-error' : 'text-accent-400'
                    }`}
                />
            </div>

            {groups.length === 0 ? (
                <div className='flex flex-1 flex-col items-center justify-center py-10 text-center'>
                    <CheckCircleIcon className='h-8 w-8 text-success' />
                    <p className='mt-3 text-sm font-medium text-foreground'>
                        {t('attention_all_clear')}
                    </p>
                    <p className='description-small mt-1'>
                        {t('attention_all_clear_detail')}
                    </p>
                </div>
            ) : (
                <div className='mt-4 space-y-1'>
                    {groups.map(group => (
                        <GroupRow key={group.key} group={group} />
                    ))}
                </div>
            )}
        </Card>
    )
}

export default AttentionCard
