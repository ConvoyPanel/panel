import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormEvent, useState } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import deleteServer from '@/api/admin/servers/deleteServer'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'
import Modal from '@/components/elements/Modal'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'


const DeleteServerCard = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.servers.${server.uuid}.settings.general.delete`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.servers.settings')
    const [showConfirmation, setShowConfirmation] = useState(false)

    const schema = z.object({
        noPurge: z.boolean(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            noPurge: false,
        },
    })

    const submit = async ({ noPurge }: z.infer<typeof schema>) => {
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
    }

    const triggerConfirmation = (e: FormEvent) => {
        e.preventDefault()
        setShowConfirmation(true)
    }

    return (
        <>
            <FormCard className='w-full border-error'>
                <FormProvider {...form}>
                    <form onSubmit={triggerConfirmation}>
                        <FormCard.Body>
                            <FormCard.Title>
                                {t('deletion.title')}
                            </FormCard.Title>
                            <div className='space-y-3 mt-3'>
                                <FlashMessageRender
                                    byKey={`admin.servers.${server.uuid}.settings.general.delete`}
                                />

                                <p className='description-small !text-foreground'>
                                    {t('deletion.description')}
                                </p>
                                {server.status === 'deleting' && (
                                    <MessageBox title='Warning' type='warning'>
                                        {t('deletion.deleting_status')}
                                    </MessageBox>
                                )}

                                <CheckboxForm
                                    name={'noPurge'}
                                    label={
                                        t('deletion.no_purge') ??
                                        'Do not purge VM and related files'
                                    }
                                />
                            </div>
                        </FormCard.Body>
                        <FormCard.Footer>
                            <Button
                                loading={form.formState.isSubmitting}
                                disabled={server.status === 'deleting'}
                                type='submit'
                                variant='filled'
                                color='danger'
                                size='sm'
                            >
                                {tStrings('delete')}
                            </Button>
                        </FormCard.Footer>
                    </form>
                </FormProvider>
            </FormCard>

            <Modal
                open={showConfirmation}
                onClose={() => setShowConfirmation(false)}
            >
                <Modal.Header>
                    <Modal.Title>
                        {t('deletion.confirmation.title', {
                            name: server.name,
                        })}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description>
                        {t('deletion.confirmation.description', {
                            name: server.name,
                        })}
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action
                        type='button'
                        onClick={() => setShowConfirmation(false)}
                    >
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action
                        type='button'
                        loading={form.formState.isSubmitting}
                        onClick={form.handleSubmit(submit)}
                    >
                        {tStrings('delete')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
        </>
    )
}

export default DeleteServerCard