import { FormikProvider } from 'formik'
import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import UsersSelectFormik from '@/components/admin/servers/UsersSelectFormik'
import NodesSelectFormik from '@/components/admin/servers/NodesSelectFormik'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import Button from '@/components/elements/Button'
import MessageBox from '@/components/elements/MessageBox'
import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import unsuspendServer from '@/api/admin/servers/unsuspendServer'
import suspendServer from '@/api/admin/servers/suspendServer'
import { useState } from 'react'

const SuspensionContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:suspension')
    const [loading, setLoading] = useState(false)

    const handle = async () => {
        clearFlashes()
        setLoading(true)
        try {
            if (server.status === 'suspended') {
                unsuspendServer(server.uuid)
                setServer({
                    ...server,
                    status: null,
                })
            } else {
                suspendServer(server.uuid)
                setServer({
                    ...server,
                    status: 'suspended',
                })
            }
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setLoading(false)
    }

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Suspension</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender byKey='admin:node:settings:suspension' />
                    <p className='description-small !text-foreground'>Toggle the suspension status of the server.</p>

                    <MessageBox title='Status' type={server.status === 'suspended' ? 'error' : 'success'}>
                        {server.status === 'suspended' ? 'This server is suspended' : "This server isn't suspended"}
                    </MessageBox>
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button loading={loading} onClick={handle} variant='filled' color='success' size='sm'>
                    {server.status === 'suspended' ? 'Unsuspend' : 'Suspend'}
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

export default SuspensionContainer
