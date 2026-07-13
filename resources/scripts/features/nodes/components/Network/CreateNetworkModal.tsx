import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { NetworkInterface } from '@/types/network-interface.ts'

import {
    createNetworkInterface,
    networkInterfaceSchema,
    networkInterfaceQueries,
} from '@/features/nodes/network-interfaces/api.ts'

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
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'

const CreateNetworkModal = () => {
    const { nodeId } = NetworkRoute.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(networkInterfaceSchema),
        defaultValues: {
            name: '',
            description: '',
            isVlanAware: false,
            vlanTag: '',
        },
    })
    const isVlanAware = form.watch('isVlanAware')

    useEffect(() => {
        if (!isVlanAware) {
            form.setValue('vlanTag', '')
        }
    }, [form, isVlanAware])

    const submit = async (data: z.infer<typeof networkInterfaceSchema>) => {
        try {
            const networkInterface = await createNetworkInterface(Number(nodeId), data)

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
                    <IconPlus className={'size-4'} /> Add network interface
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
                            <CheckboxForm
                                name={'isVlanAware'}
                                label={'VLAN-aware bridge'}
                                description={
                                    'Enable this only when the Proxmox bridge has VLAN awareness enabled.'
                                }
                            />
                            <InputForm
                                name={'vlanTag'}
                                label={'Default VLAN tag'}
                                type={'number'}
                                min={1}
                                max={4094}
                                disabled={!isVlanAware}
                                description={
                                    'Optional. Servers on this interface inherit this tag unless they set an override.'
                                }
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
