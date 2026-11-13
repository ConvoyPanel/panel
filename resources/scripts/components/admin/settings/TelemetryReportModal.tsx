import { useEffect, useState } from 'react'

import { getTelemetryReport } from '@/api/admin/settings/telemetry'

import Modal from '@/components/elements/Modal'
import Spinner from '@/components/elements/Spinner'

interface Props {
    open: boolean
    onClose: () => void
}

/**
 * The report exactly as the next one would be sent, built by the same code as
 * the daily report. Opening it changes nothing.
 */
const TelemetryReportModal = ({ open, onClose }: Props) => {
    const [report, setReport] = useState<string | null>(null)
    const [error, setError] = useState(false)

    useEffect(() => {
        if (!open) {
            return
        }

        setReport(null)
        setError(false)

        getTelemetryReport()
            .then(data => setReport(JSON.stringify(data, null, 4)))
            .catch(() => setError(true))
    }, [open])

    return (
        <Modal open={open} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>Telemetry report</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Modal.Description bottomMargin>
                    This is the report as the next one would be sent: counts,
                    switches and version numbers under a random installation id.
                    Nothing in it names a person or this panel.
                </Modal.Description>
                {error ? (
                    <p className='text-sm text-error'>
                        The report could not be built. Try again in a moment.
                    </p>
                ) : report === null ? (
                    <div className='grid place-items-center py-10'>
                        <Spinner />
                    </div>
                ) : (
                    <pre className='max-h-[60vh] overflow-auto rounded border border-accent-200 bg-background p-4 font-mono text-xs text-foreground'>
                        {report}
                    </pre>
                )}
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action onClick={onClose}>Close</Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default TelemetryReportModal
