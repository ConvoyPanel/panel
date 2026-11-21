import {
    serverGeneralSchema,
    serverQueries,
    updateServerGeneral,
} from '@/features/servers/admin/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Server } from '@/types/server.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

type GeneralInput = z.infer<typeof serverGeneralSchema>

/**
 * The general-info fields an admin can change from here. Only the external ID
 * for now; it saves through its own endpoint, apart from the build form below.
 */
const ServerGeneralCard = ({ server }: { server: Server }) => {
    const mutateServer = useQueryMutator<Server>(
        serverQueries.detail(server.id).queryKey
    )
    const mutateServerLists = useQueryMutator(serverQueries.lists())

    const form = useForm<GeneralInput>({
        resolver: zodResolver(serverGeneralSchema),
        values: { externalId: server.externalId ?? '' },
    })

    const { mutateAsync: save } = useMutation({
        mutationFn: (data: GeneralInput) =>
            updateServerGeneral(server.id, data),
    })

    const submit = async (data: GeneralInput) => {
        try {
            const updatedServer = await save(data)
            await mutateServer(() => updatedServer, false)
            await mutateServerLists()
            toast.add({ title: 'Server updated', type: 'success' })
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to update server', type: 'error' })
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>General</CardTitle>
            </CardHeader>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <CardContent>
                        <InputForm
                            name={'externalId'}
                            label={'External ID'}
                            autoComplete={'off'}
                        />
                    </CardContent>
                    <CardFooter className={'justify-end'}>
                        <FormButton
                            className={'flex'}
                            disabled={!form.formState.isDirty}
                        >
                            Save changes <IconCheck className={'size-4'} />
                        </FormButton>
                    </CardFooter>
                </form>
            </Form>
        </Card>
    )
}

export default ServerGeneralCard
