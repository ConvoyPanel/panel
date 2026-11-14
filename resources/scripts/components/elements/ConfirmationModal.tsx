import { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

import Modal from '@/components/elements/Modal'

interface Props {
    open: boolean
    title: ReactNode
    description: ReactNode
    confirmLabel: ReactNode
    loading?: boolean
    onConfirm: () => void
    onClose: () => void
}

/**
 * "Are you sure?" before something that can't be undone, such as deleting a
 * user or an ISO, which used to happen on the first click.
 */
const ConfirmationModal = ({
    open,
    title,
    description,
    confirmLabel,
    loading,
    onConfirm,
    onClose,
}: Props) => {
    const { t: tStrings } = useTranslation('strings')

    return (
        <Modal open={open} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>{title}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Modal.Description>{description}</Modal.Description>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action type='button' onClick={onClose}>
                    {tStrings('cancel')}
                </Modal.Action>
                <Modal.Action
                    type='button'
                    loading={loading}
                    onClick={onConfirm}
                >
                    {confirmLabel}
                </Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default ConfirmationModal
