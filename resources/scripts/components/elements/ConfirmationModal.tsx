import { ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'

import Modal from '@/components/elements/Modal'

interface Props {
    open: boolean
    title: ReactNode
    description: ReactNode
    confirmLabel: ReactNode
    onConfirm: () => Promise<unknown>
    onClose: () => void
}

/**
 * Stays open, and can't be dismissed, until the promise from `onConfirm`
 * settles.
 */
const ConfirmationModal = ({
    open,
    title,
    description,
    confirmLabel,
    onConfirm,
    onClose,
}: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const [loading, setLoading] = useState(false)

    const close = () => {
        if (!loading) onClose()
    }

    const confirm = async () => {
        setLoading(true)
        try {
            await onConfirm()
        } finally {
            setLoading(false)
            onClose()
        }
    }

    return (
        <Modal open={open} onClose={close}>
            <Modal.Header>
                <Modal.Title>{title}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Modal.Description>{description}</Modal.Description>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action type='button' disabled={loading} onClick={close}>
                    {tStrings('cancel')}
                </Modal.Action>
                <Modal.Action type='button' loading={loading} onClick={confirm}>
                    {confirmLabel}
                </Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default ConfirmationModal
