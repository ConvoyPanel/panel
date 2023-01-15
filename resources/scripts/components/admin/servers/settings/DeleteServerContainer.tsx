import deleteServer from '@/api/admin/servers/deleteServer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import MessageBox from '@/components/elements/MessageBox'
import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'

const DeleteServerContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:delete')
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)
    const [loading, setLoading] = useState(false)

    const handleDelete = async () => {
        clearFlashes()
        setLoading(true)
        try {
            await deleteServer(server.uuid)

            setServer({
                ...server,
                status: 'deleting',
            })
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setLoading(false)
    }

    return (
            <FormCard className='w-full border-error'>
                <FormCard.Body>
                    <FormCard.Title>Delete Server</FormCard.Title>
                    <div className='space-y-3 mt-3'>
                        <FlashMessageRender byKey='admin:server:settings:delete' />

                        <p className='description-small !text-foreground'>
                            The node will be permanently deleted from Convoy. This action is irreversible and can not be
                            undone.
                        </p>
                        {server.status === 'deleting' && <MessageBox title='Warning' type='warning'>
                            This server is currently being deleted.
                        </MessageBox>}
                    </div>
                </FormCard.Body>
                <FormCard.Footer>
                    <Button
                        onClick={handleDelete}
                        loading={loading}
                        disabled={server.status === 'deleting'}
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

export default DeleteServerContainer
