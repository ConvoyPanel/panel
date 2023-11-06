import { Code } from '@mantine/core'
import { useTranslation } from 'react-i18next'

import Modal from '@/components/elements/Modal'

interface Props {
    value: string | null
    onClose: () => void
}

const CotermTokenModal = ({ value, onClose }: Props) => {
    const { t } = useTranslation('admin.nodes.settings')

    return (
        <Modal open={Boolean(value)} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>{t('coterm.token_created.title')}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <Modal.Description>
                    {t('coterm.token_created.description')}
                </Modal.Description>

                <Code className={'!mt-3'} block color={'blue'}>
                    {value}
                </Code>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action onClick={onClose}>
                    {t('coterm.token_created.action')}
                </Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default CotermTokenModal