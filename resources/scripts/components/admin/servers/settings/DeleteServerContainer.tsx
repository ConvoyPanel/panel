import deleteServer from '@/api/admin/servers/deleteServer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import MessageBox from '@/components/elements/MessageBox'
import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { FormikProvider, useFormik } from 'formik'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'

const DeleteServerContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:server:settings:delete')
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(actions => actions.server.setServer)

    const form = useFormik({
        initialValues: {
            noPurge: false,
        },
        onSubmit: async ({ noPurge }) => {
            clearFlashes()
            try {
                await deleteServer(server.uuid, noPurge)

                setServer({
                    ...server,
                    status: 'deleting',
                })
            } catch (error) {
                clearAndAddHttpError(error as any)
            }
        },
    })

    return (
        <FormCard className='w-full border-error'>
            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Delete Server</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey='admin:server:settings:delete' />

                            <p className='description-small !text-foreground'>
                                The server will be deleted from Convoy. Backups and other associated data will be
                                destroyed. However, you can tick the checkbox below to keep the virtual machine and data
                                on the Proxmox node.
                            </p>
                            {server.status === 'deleting' && (
                                <MessageBox title='Warning' type='warning'>
                                    This server is currently being deleted.
                                </MessageBox>
                            )}

                            <CheckboxFormik name={'noPurge'} label={'Do not purge VM and related files'} />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.isSubmitting}
                            disabled={server.status === 'deleting'}
                            type='submit'
                            variant='filled'
                            color='danger'
                            size='sm'
                        >
                            Delete
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormikProvider>
        </FormCard>
    )
}

export default DeleteServerContainer
