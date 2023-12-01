import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import { Coterm } from '@/api/admin/coterms/getCoterms'
import resetCotermToken from '@/api/admin/coterms/resetCotermToken'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'


interface Props {
    coterm: Coterm | null
    onReset: (token: string) => void
    onClose: () => void
}

const CotermResetModal = ({ coterm, onReset, onClose }: Props) => {
    const { t } = useTranslation('admin.nodes.settings')
    const [loading, setLoading] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.coterms.${coterm?.id}.reset`
    )

    const reset = async () => {
        clearFlashes()

        try {
            setLoading(true)
            const updatedCoterm = await resetCotermToken(coterm.id)

            onReset(`${updatedCoterm!.tokenId}|${updatedCoterm!.token}`)
        } catch (e) {
            clearAndAddHttpError(e as Error)
        } finally {
            setLoading(false)
        }
    }

    return (
        <Modal open={Boolean(coterm)} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>{t('coterm.reset.title')}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <FlashMessageRender
                    className='mb-3'
                    byKey={`admin.coterms.${coterm?.id}.reset`}
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