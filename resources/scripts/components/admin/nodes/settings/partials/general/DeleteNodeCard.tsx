import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

import deleteNode from '@/api/admin/nodes/deleteNode'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'

import DeleteNodeModal from '@/components/admin/nodes/DeleteNodeModal'


const DeleteNodeCard = () => {
    const { data: node } = useNodeSWR()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.nodes.${node.id}.settings.general.delete`
    )
    const navigate = useNavigate()
    const [confirming, setConfirming] = useState(false)

    const handleDelete = async () => {
        clearFlashes()
        try {
            await deleteNode(node.id)

            navigate('/admin/nodes')
        } catch (error) {
            clearAndAddHttpError(error as Error)
        }
    }

    return (
        <FormCard className='w-full border-error'>
            <DeleteNodeModal
                open={confirming}
                name={node.name}
                onConfirm={handleDelete}
                onClose={() => setConfirming(false)}
            />
            <FormCard.Body>
                <FormCard.Title>Delete Node</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender
                        byKey={`admin.nodes.${node.id}.settings.general.delete`}
                    />

                    {node.serversCount > 0 && (
                        <MessageBox title='Error' type='error'>
                            Delete or disconnect this node's servers first.
                        </MessageBox>
                    )}
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button
                    onClick={() => setConfirming(true)}
                    disabled={node.serversCount > 0}
                    type='submit'
                    variant='filled'
                    color='danger'
                    size='sm'
                >
                    Delete
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

export default DeleteNodeCard