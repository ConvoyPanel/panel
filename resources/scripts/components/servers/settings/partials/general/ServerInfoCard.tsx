import renameServer from '@/api/server/settings/renameServer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import { ServerContext } from '@/state/server'
import { useFlashKey } from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { hostname } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { z } from 'zod'

const ServerInfoCard = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`servers.${server.uuid}.settings.general`)
    const notify = useNotify()

    const schema = z.object({
        name: z.string().min(1).max(40),
        hostname: hostname().max(191),
    })

    const methods = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: server.name,
            hostname: server.hostname,
        }
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            await renameServer(server.uuid, data)

            notify({
                title: 'Updated',
                message: 'Updated general settings',
                color: 'green',
            })

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
                    <FormCard.Title>Server Name</FormCard.Title>
                    <div className='mt-3'>
                        <FlashMessageRender byKey='server:settings:general:rename' />
                        <TextInputForm
                            name='name'
                            label='Display Name'
                        />
                        <TextInputForm
                            name='hostname'
                            className='mt-3'
                            label='Hostname'
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
                        Save
                    </Button>
                </FormCard.Footer>
            </form>
            </FormProvider>
        </FormCard>
    )
}

export default ServerInfoCard
