import { useStoreState } from '@/state'
import {
    ChartBarIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
} from '@heroicons/react/20/solid'
import { useState } from 'react'
import { Link } from 'react-router-dom'

import useAnnouncementsSWR from '@/api/admin/announcements/useAnnouncementsSWR'
import { dismissTelemetryNotice } from '@/api/admin/settings/telemetry'

import ContentContainer from '@/components/elements/ContentContainer'

import AnnouncementBody from '@/components/admin/announcements/AnnouncementBody'
import TelemetryReportModal from '@/components/admin/settings/TelemetryReportModal'

/**
 * Shown under the navigation bar on every page an admin opens: critical
 * announcements that apply to this panel, which can't be dismissed, and the
 * one-time notice that telemetry is on.
 */
const AdminNotices = () => {
    const rootAdmin = useStoreState(state => state.user.data?.rootAdmin)
    const { data, mutate } = useAnnouncementsSWR(Boolean(rootAdmin))
    const [reportOpen, setReportOpen] = useState(false)

    if (!rootAdmin || !data) {
        return null
    }

    const hideTelemetryNotice = () => {
        mutate({ ...data, telemetryNotice: false }, false)
        dismissTelemetryNotice().catch(() => mutate())
    }

    return (
        <>
            {data.banner.map(announcement => (
                <div
                    key={announcement.id}
                    role='alert'
                    className='bg-error text-white'
                >
                    <ContentContainer className='flex gap-3 py-3'>
                        <ExclamationTriangleIcon className='mt-0.5 h-5 w-5 shrink-0' />
                        <div className='min-w-0 text-sm'>
                            <p className='font-semibold'>
                                {announcement.title}
                            </p>
                            <AnnouncementBody
                                body={announcement.body}
                                className='mt-1 text-white/90'
                            />
                            <p className='mt-2 flex flex-wrap gap-x-4 gap-y-1 font-medium'>
                                <a
                                    href={announcement.url}
                                    target='_blank'
                                    rel='noopener noreferrer'
                                    className='underline underline-offset-2'
                                >
                                    Read the announcement
                                </a>
                                <Link
                                    to='/admin/settings/updates'
                                    className='underline underline-offset-2'
                                >
                                    Why this panel is affected
                                </Link>
                            </p>
                        </div>
                    </ContentContainer>
                </div>
            ))}
            {data.telemetryNotice && (
                <div className='border-b border-accent-200 bg-accent-100'>
                    <ContentContainer className='flex items-start gap-3 py-3'>
                        <ChartBarIcon className='mt-0.5 h-5 w-5 shrink-0 text-accent-500' />
                        <div className='min-w-0 flex-1 text-sm text-foreground'>
                            <p>
                                <span className='font-semibold'>
                                    Convoy now sends an anonymous usage report
                                    once a day.
                                </span>{' '}
                                It holds counts and version numbers under a
                                random id, and never names, addresses or
                                anything typed into the panel.
                            </p>
                            <p className='mt-2 flex flex-wrap gap-x-4 gap-y-1 font-medium'>
                                <button
                                    type='button'
                                    className='link text-foreground'
                                    onClick={() => setReportOpen(true)}
                                >
                                    View the report
                                </button>
                                <Link
                                    to='/admin/settings/telemetry'
                                    className='link text-foreground'
                                >
                                    Turn it off
                                </Link>
                            </p>
                        </div>
                        <button
                            type='button'
                            aria-label='Dismiss'
                            title='Dismiss'
                            className='rounded p-1 text-accent-500 transition-colors hover:bg-accent-200 hover:text-foreground'
                            onClick={hideTelemetryNotice}
                        >
                            <XMarkIcon className='h-5 w-5' />
                        </button>
                    </ContentContainer>
                    <TelemetryReportModal
                        open={reportOpen}
                        onClose={() => setReportOpen(false)}
                    />
                </div>
            )}
        </>
    )
}

export default AdminNotices
