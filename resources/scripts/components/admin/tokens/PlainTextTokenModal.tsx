import { Code } from '@mantine/core'

import { Token } from '@/api/admin/tokens/getTokens'

import Modal from '@/components/elements/Modal'

interface Props {
    value: Token | null
    onClose: () => void
}

const PlainTextTokenModal = ({ value, onClose }: Props) => {
    return (
        <Modal open={Boolean(value)} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>Token Created</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <Modal.Description>
                    Copy this token now. You won't be able to see it again.
                </Modal.Description>

                <Code className={'!mt-3'} block color={'blue'}>
                    {value?.plainTextToken}
                </Code>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action onClick={onClose}>Done</Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default PlainTextTokenModal