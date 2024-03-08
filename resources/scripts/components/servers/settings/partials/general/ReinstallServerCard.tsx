import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { password, usKeyboardCharacters } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import reinstallServer from '@/api/server/settings/reinstallServer'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import Modal from '@/components/elements/Modal'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import TemplatesSelectForm from '@/components/servers/settings/TemplatesSelectForm'


const ReinstallServerCard = () => {
    const { t } = useTranslation('server.settings')
    const { t: tStrings } = useTranslation('strings')
    const [isModalOpen, setIsModalOpen] = useState(false)
    const server = ServerContext.useStoreState(state => state.server.data!)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${server.uuid}.settings.general.reinstall`
    )
    const setServer = ServerContext.useStoreActions(
        state => state.server.setServer
    )

    const schema = z.object({
        templateUuid: z.string().nonempty(),
        accountPassword: password(usKeyboardCharacters()).nonempty(),
        startOnCompletion: z.boolean(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        mode: 'onChange',
        defaultValues: {
            templateUuid: '',
            accountPassword: '',
            startOnCompletion: false,
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()
        try {
            await reinstallServer(server.uuid, data)

            setServer({
                ...server,
                status: 'installing',
            })
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
    }

    return (
        <>
            <Modal open={isModalOpen} onClose={() => setIsModalOpen(false)}>
                <Modal.Header>
                    <Modal.Title>{t('reinstall.modal.title')}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description bottomMargin>
                        {t('reinstall.modal.description')}
                    </Modal.Description>
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setIsModalOpen(false)}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action
                        loading={form.formState.isSubmitting}
                        onClick={form.handleSubmit(submit)}
                    >
                        {tStrings('confirm')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
            <FormProvider {...form}>
                <FormCard className='w-full'>
                    <FormCard.Body>
                        <FormCard.Title>{t('reinstall.title')}</FormCard.Title>
                        <FlashMessageRender
                            className='mt-3'
                            byKey={`servers.${server.uuid}.settings.general.reinstall`}
                        />
                        <p className='description-small mt-3'>
                            {t('reinstall.description')}
                        </p>
                        <div className='flex flex-col space-y-3 mt-3'>
                            <TemplatesSelectForm />
                            <TextInputForm
                                name={'accountPassword'}
                                label={tStrings('system_os_password')}
                                type={'password'}
                            />
                            <CheckboxForm
                                name={'startOnCompletion'}
                                label={t(
                                    'reinstall.start_server_after_installing'
                                )}
                            />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer className='flex justify-center md:justify-end'>
                        <Button
                            disabled={!form.formState.isValid}
                            onClick={() => setIsModalOpen(true)}
                            type={'submit'}
                            variant='filled'
                            color='danger'
                            size='sm'
                        >
                            {tStrings('reinstall')}
                        </Button>
                    </FormCard.Footer>
                </FormCard>
            </FormProvider>
        </>
    )
}

export default ReinstallServerCard
