import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'

import deleteServer from '@/api/admin/servers/deleteServer'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'


type Action = 'delete' | 'disconnect'

interface Props {
    className?: string
}

// Delete and Disconnect, with their confirmations. Shared by the settings card
// and the admin's "Deleting"/"Failed to Delete" screen, where the settings
// aren't reachable.
const DeleteServerActions = ({ className }: Props) => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.servers.${server.uuid}.settings.general.delete`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.servers.settings')
    const navigate = useNavigate()
    const [confirming, setConfirming] = useState<Action | null>(null)
    const [submitting, setSubmitting] = useState(false)

    const submit = async (action: Action) => {
        clearFlashes()
        setSubmitting(true)
        try {
            // Disconnecting is the API's no_purge: only the Convoy entry goes.
            await deleteServer(server.uuid, action === 'disconnect')

            if (action === 'disconnect') {
                navigate('/admin/servers')
                return
            }

            setServer({ ...server, status: 'deleting' })
            setConfirming(null)
        } catch (error) {
            clearAndAddHttpError(error as any)
            setConfirming(null)
        } finally {
            setSubmitting(false)
        }
    }

    return (
        <>
            <FlashMessageRender
                byKey={`admin.servers.${server.uuid}.settings.general.delete`}
                className='mb-3 text-left'
            />
            <div className={`flex justify-end gap-2 ${className ?? ''}`}>
                <Button
                    type='button'
                    variant='outline'
                    color='danger'
                    size='sm'
                    onClick={() => setConfirming('disconnect')}
                >
                    {t('deletion.disconnect')}
                </Button>
                <Button
                    type='button'
                    variant='filled'
                    color='danger'
                    size='sm'
                    disabled={server.status === 'deleting'}
                    onClick={() => setConfirming('delete')}
                >
                    {tStrings('delete')}
                </Button>
            </div>

            <Modal open={confirming !== null} onClose={() => setConfirming(null)}>
                <Modal.Header>
                    <Modal.Title>
                        {confirming === 'disconnect'
                            ? t('deletion.disconnect_confirmation.title', {
                                  name: server.name,
                              })
                            : t('deletion.confirmation.title', {
                                  name: server.name,
                              })}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        {confirming === 'disconnect'
                            ? t('deletion.disconnect_confirmation.description', {
                                  name: server.name,
                              })
                            : t('deletion.confirmation.description', {
                                  name: server.name,
                              })}
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action type='button' onClick={() => setConfirming(null)}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action
                        type='button'
                        loading={submitting}
                        onClick={() => confirming && submit(confirming)}
                    >
                        {confirming === 'disconnect'
                            ? t('deletion.disconnect')
                            : tStrings('delete')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
        </>
    )
}

export default DeleteServerActions
