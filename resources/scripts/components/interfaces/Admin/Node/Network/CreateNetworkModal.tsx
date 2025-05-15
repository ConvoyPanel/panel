import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import createNetworkInterface, {
    networkInterfaceSchema,
} from '@/api/admin/nodes/networkInterfaces/createNetworkInterface.ts'
import useNetworkInterfacesSWR from '@/api/admin/nodes/networkInterfaces/use-network-interfaces-swr.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'

const CreateNetworkModal = () => {
    const { nodeId } = NetworkRoute.useParams()
    const { mutate } = useNetworkInterfacesSWR()
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(networkInterfaceSchema),
        defaultValues: {
            name: '',
            description: '',
            mtu: '',
        },
    })

    const submit = async (data: z.infer<typeof networkInterfaceSchema>) => {
        try {
            const networkInterface = await createNetworkInterface(nodeId, data)

            await mutate(data => {
                if (!data) return

                return data.concat(networkInterface)
            }, false)

            form.reset()
            setOpen(false)
            toast.success('Network interface created')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button size={'sm'} className={'self-end'}>
                    <IconPlus className={'mr-2 size-4'} /> Add network interface
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>New Network Interface</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody className={'space-y-2'}>
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                autoComplete={'off'}
                            />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                            <InputForm
                                name={'mtu'}
                                label={'MTU'}
                                type={'number'}
                                min={1}
                                max={65535}
                                placeholder={'1500'}
                                autoComplete={'off'}
                            />
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Add network</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateNetworkModal
