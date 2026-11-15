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
                <Modal.Title>Telemetry Report</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Modal.Description bottomMargin>
                    This is the next report, exactly as it'll be sent. Nothing
                    in it identifies a person or this panel.
                </Modal.Description>
                {error ? (
                    <p className='text-sm text-error'>
                        Couldn't build the report. Try again in a moment.
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
