import {
    ArrowRightIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'
import { ComponentType, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import {
    AttentionSubject,
    DashboardOverview,
} from '@/api/admin/overview/getOverview'

import Card from '@/components/elements/Card'
import Modal from '@/components/elements/Modal'

interface IconProps {
    className?: string
}

interface AttentionGroup {
    key: string
    icon: ComponentType<IconProps>
    /** Summary line used when the group holds more than one record. */
    title: string
    actionLabel: string
    /** The records themselves, capped by the endpoint. */
    subjects: AttentionSubject[]
    /** Destination for one record. */
    to: (subject: AttentionSubject) => string
    /** Real total, which can exceed what `subjects` carries. */
    total: number
}

const iconClasses = 'text-error border-error-light bg-error-lighter'

const SubjectRow = ({
    subject,
    to,
    onNavigate,
}: {
    subject: AttentionSubject
    to: string
    onNavigate: () => void
}) => (
    <Link
        to={to}
        onClick={onNavigate}
        className='block rounded px-3 py-2 transition-colors hover:bg-accent-100'
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
 * count does not. The names truncate and the overflow count does not: baked into
 * one string, `truncate` cuts from the end, so "and 3 more" -- the only part the
 * count does not already say -- was the first thing to go. They are returned
 * separately so the row can let the names shrink around a pinned suffix.
 */
const preview = (group: AttentionGroup) => ({
    names: group.subjects.map(subject => subject.label).join(', '),
    hidden: group.total - group.subjects.length,
})

/**
 * One group. A single record is named outright and links straight at itself --
 * "1 server failed to install" plus a hunt through the server list is strictly
 * less information than naming the server. Several open a sheet over direct
 * links, because the count alone is what made the old card useless.
 *
 * The sheet is what keeps the dashboard still. Expanding 25 records inline grew
 * the card by several hundred pixels and shoved every card below it down the
 * page, so reading one row rearranged the rest -- and the group most worth
 * opening moved the page most. Modal.Body is the panel's scrolling section and
 * the list is bounded however broken the fleet is.
 */
const GroupRow = ({ group }: { group: AttentionGroup }) => {
    const [open, setOpen] = useState(false)
    const { t } = useTranslation('admin.overview')
    const Icon = group.icon
    const truncated = group.total > group.subjects.length
    const { names, hidden } = preview(group)

    const icon = (
        <div className={`shrink-0 rounded-md border p-2 ${iconClasses}`}>
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
                    {subject.detail && (
                        <p className='description-small truncate'>
                            {subject.detail}
                        </p>
                    )}
                </div>
                <span className='link flex shrink-0 items-center gap-1 text-sm'>
                    {group.actionLabel}
                    <ArrowRightIcon className='h-4 w-4' />
                </span>
            </Link>
        )
    }

    return (
        <>
            <button
                type='button'
                onClick={() => setOpen(true)}
                aria-haspopup='dialog'
                aria-expanded={open}
                className='flex w-full items-center gap-3 rounded border border-transparent bg-transparent px-2 py-3 text-left transition-colors hover:border-accent-200 hover:bg-accent-100'
            >
                {icon}
                <div className='min-w-0 flex-1'>
                    <p className='truncate text-sm font-medium text-foreground'>
                        {group.title}
                    </p>
                    <p className='description-small flex min-w-0 gap-1'>
                        <span className='truncate'>{names}</span>
                        {hidden > 0 && (
                            <span className='shrink-0'>
                                {t('attention_and_more', { count: hidden })}
                            </span>
                        )}
                    </p>
                </div>
                <span className='link flex shrink-0 items-center gap-1 text-sm'>
                    {group.actionLabel}
                    <ArrowRightIcon className='h-4 w-4' />
                </span>
            </button>
            <Modal open={open} onClose={() => setOpen(false)}>
                <Modal.Header>
                    <Modal.Title>{group.title}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className='divide-y divide-accent-200'>
                        {group.subjects.map((subject, index) => (
                            <SubjectRow
                                // One server can own two failed backups with the
                                // same name, so id+label is not unique; the list is
                                // a static snapshot that never reorders, so the
                                // index is a safe tiebreaker.
                                key={`${subject.id}-${subject.label}-${index}`}
                                subject={subject}
                                to={group.to(subject)}
                                onNavigate={() => setOpen(false)}
                            />
                        ))}
                    </div>
                    {truncated && (
                        <p className='description-small px-3 pt-3'>
                            {t('attention_showing', {
                                shown: group.subjects.length,
                                total: group.total,
                            })}
                        </p>
                    )}
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setOpen(false)}>
                        {t('attention_close')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
        </>
    )
}

const AttentionCard = ({ data }: { data: DashboardOverview }) => {
    const { t } = useTranslation('admin.overview')
    const { attention, summary, backups } = data

    const groups: AttentionGroup[] = []

    if (attention.failedServers.length > 0) {
        groups.push({
            key: 'failed-servers',
            icon: ExclamationTriangleIcon,
            title: t('attention_failed_servers', {
                count: summary.failedServers,
            }),
            actionLabel: t('attention_fix'),
            subjects: attention.failedServers,
            to: subject => `/admin/servers/${subject.id}`,
            total: summary.failedServers,
        })
    }

    if (attention.failedBackups.length > 0) {
        groups.push({
            key: 'failed-backups',
            icon: ExclamationTriangleIcon,
            title: t('attention_failed_backups', { count: backups.failed }),
            actionLabel: t('attention_view'),
            // The backups tab is the only page that shows a backup, and it is
            // keyed by the server's short uuid -- the same key the admin server
            // routes bind on.
            to: subject => `/servers/${subject.id}/backups`,
            subjects: attention.failedBackups,
            total: backups.failed,
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
