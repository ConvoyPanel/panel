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
                    Here is your newly created API token. Please take note of
                    the token's value as this is the only and last time you will
                    see it.
                </Modal.Description>

                <Code className={'!mt-3'} block color={'blue'}>
                    {value?.plainTextToken}
                </Code>
            </Modal.Body>
            <Modal.Actions>
                <Modal.Action onClick={onClose}>Okay, I got it</Modal.Action>
            </Modal.Actions>
        </Modal>
    )
}

export default PlainTextTokenModal