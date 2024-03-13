import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import { hostname } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import renameServer from '@/api/server/settings/renameServer'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputForm from '@/components/elements/forms/TextInputForm'


const ServerInfoCard = () => {
    const { t } = useTranslation('server.settings')
    const { t: tStrings } = useTranslation('strings')
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${server.uuid}.settings.general.info`
    )

    const schema = z.object({
        name: z.string().nonempty().max(40),
        hostname: hostname().max(191),
    })

    const methods = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: server.name,
            hostname: server.hostname,
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await renameServer(server.uuid, data)

            setServer({ ...server, name: data.name })
            methods.reset(data)
        } catch (e) {
            console.error(e)

            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <FormCard className='w-full'>
            <FormProvider {...methods}>
                <form onSubmit={methods.handleSubmit(submit)}>
                    <FormCard.Body>
                        <FormCard.Title>
                            {t('display_info.title')}
                        </FormCard.Title>
                        <div className='mt-3'>
                            <FlashMessageRender
                                byKey={`servers.${server.uuid}.settings.general.info`}
                            />
                            <TextInputForm
                                name='name'
                                label={tStrings('display_name')}
                            />
                            <TextInputForm
                                name='hostname'
                                className='mt-3'
                                label={tStrings('hostname')}
                            />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            disabled={!methods.formState.isDirty}
                            loading={methods.formState.isSubmitting}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                        >
                            {tStrings('save')}
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormProvider>
        </FormCard>
    )
}

export default ServerInfoCard