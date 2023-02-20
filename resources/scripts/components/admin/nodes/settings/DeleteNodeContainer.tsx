import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import MessageBox from '@/components/elements/MessageBox'
import { NodeContext } from '@/state/admin/node'
import { useFlashKey } from '@/util/useFlash'
import { useNavigate } from 'react-router-dom'
import deleteNode from '@/api/admin/nodes/deleteNode'
import { useState } from 'react'

const DeleteNodeContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:node:settings:delete')
    const node = NodeContext.useStoreState(state => state.node.data!)
    const navigate = useNavigate()
    const [loading, setLoading] = useState(false)

    const handleDelete = async () => {
        clearFlashes()
        setLoading(true)
        try {
            await deleteNode(node.id)

            navigate('/admin/nodes')
        } catch (error) {
            clearAndAddHttpError(error as Error)
        }
        setLoading(false)
    }

    return (
        <FormCard className='w-full border-error'>
            <FormCard.Body>
                <FormCard.Title>Delete Node</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender byKey='admin:node:settings:delete' />

                    <p className='description-small !text-foreground'>
                        The node will be permanently deleted from Convoy. This action is irreversible and can not be
                        undone.
                    </p>

                    {node.serversCount > 0 && (
                        <MessageBox title='Error' type='error'>
                            You cannot delete a node that has servers assigned to it.
                        </MessageBox>
                    )}
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button
                    onClick={handleDelete}
                    loading={loading}
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

export default DeleteNodeContainer
