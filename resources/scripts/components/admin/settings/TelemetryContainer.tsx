import { useFlashKey } from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { Skeleton } from '@mantine/core'
import { useState } from 'react'

import useAnnouncementsSWR from '@/api/admin/announcements/useAnnouncementsSWR'
import {
    updateTelemetrySettings,
    useTelemetrySettingsSWR,
} from '@/api/admin/settings/telemetry'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import Switch from '@/components/elements/inputs/Switch'

import TelemetryReportModal from '@/components/admin/settings/TelemetryReportModal'

const TelemetryContainer = () => {
    const { data, mutate } = useTelemetrySettingsSWR()
    const { mutate: mutateAnnouncements } = useAnnouncementsSWR()
    const [saving, setSaving] = useState(false)
    const [reportOpen, setReportOpen] = useState(false)
    const notify = useNotify()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin:settings:telemetry'
    )

    const toggle = async (enabled: boolean) => {
        clearFlashes()
        setSaving(true)

        try {
            await mutate(await updateTelemetrySettings(enabled), false)
            // The notice that telemetry is on goes with it.
            mutateAnnouncements()

            notify({
                color: 'green',
                message: enabled
                    ? 'Telemetry is on. The first report goes out at its time today or tomorrow.'
                    : "Telemetry is off. The Convoy console has been asked to erase this panel's reports.",
            })
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }

        setSaving(false)
    }

    if (!data) {
        return <Skeleton height={300} />
    }

    return (
        <>
            <FlashMessageRender byKey='admin:settings:telemetry' />
            <FormCard>
                <FormCard.Body>
                    <FormCard.Title>Telemetry</FormCard.Title>
                    <div className='mt-3 space-y-3 text-sm text-accent-600'>
                        <p>
                            Once a day the panel sends the Convoy team an
                            anonymous report: how many accounts, nodes, servers
                            and other resources there are, the panel, PHP and
                            database versions, and which pages get opened. It is
                            filed under a random id and never holds a name,
                            email or IP address, a hostname or URL, the license
                            key, or anything typed into the panel.
                        </p>
                        <p>
                            Turning it off stops the reports and asks the Convoy
                            console to erase every report it holds for this
                            panel. The panel also gets a new id, so turning it
                            back on later starts over.
                        </p>
                    </div>

                    <div className='mt-6'>
                        <Switch
                            label='Send the daily report'
                            checked={data.enabled}
                            disabled={saving || data.disabledBy !== null}
                            onChange={event =>
                                toggle(event.currentTarget.checked)
                            }
                        />
                    </div>

                    <dl className='mt-6 space-y-1 text-sm'>
                        {data.disabledBy !== null ? (
                            <p className='text-foreground'>
                                {data.disabledBy === 'CONVOY_TELEMETRY_URL'
                                    ? "CONVOY_TELEMETRY_URL is empty in the panel's environment, so telemetry stays off."
                                    : "CONVOY_TELEMETRY_ENABLED=false in the panel's environment keeps telemetry off."}{' '}
                                The environment switch doesn't contact the
                                console, so it doesn't erase reports already
                                sent.
                            </p>
                        ) : data.enabled ? (
                            <>
                                <p className='text-accent-600'>
                                    The report goes out every day at{' '}
                                    {data.reportTime} UTC.
                                </p>
                                <p className='text-accent-600'>
                                    {data.reportedAt
                                        ? `The console last accepted a report on ${data.reportedAt.toLocaleString(
                                              undefined,
                                              {
                                                  dateStyle: 'medium',
                                                  timeStyle: 'short',
                                              }
                                          )}.`
                                        : 'No report has been sent yet.'}
                                </p>
                            </>
                        ) : null}
                    </dl>
                </FormCard.Body>
                <FormCard.Footer>
                    <Button size='sm' onClick={() => setReportOpen(true)}>
                        View report
                    </Button>
                </FormCard.Footer>
            </FormCard>
            <TelemetryReportModal
                open={reportOpen}
                onClose={() => setReportOpen(false)}
            />
        </>
    )
}

export default TelemetryContainer
