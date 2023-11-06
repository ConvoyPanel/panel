import { useFlashKey } from '@/util/useFlash'
import { Code } from '@mantine/core'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import resetCotermToken from '@/api/admin/nodes/settings/resetCotermToken'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'

interface Props {
    nodeId: number
    open: boolean
    onReset: (token: string) => void
    onClose: () => void
}

const CotermResetModal = ({ nodeId, open, onReset, onClose }: Props) => {
    const { t } = useTranslation('admin.nodes.settings')
    const [loading, setLoading] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.nodes.${nodeId}.settings.general.reset-coterm`
    )

    const reset = async () => {
        clearFlashes()

        try {
            setLoading(true)
            const token = await resetCotermToken(nodeId)

            onReset(token)
        } catch (e) {
            clearAndAddHttpError(e as Error)
        } finally {
            setLoading(false)
        }
    }

    return (
        <Modal open={open} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>{t('coterm.reset.title')}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <FlashMessageRender
                    className='mb-3'
                    byKey={`admin.nodes.${nodeId}.settings.general.reset-coterm`}
                />
                <Modal.Description>
                    {t('coterm.reset.description')}
                </Modal.Description>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action loading={loading} onClick={reset}>
                    {t('coterm.reset.action')}
                </Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default CotermResetModal
