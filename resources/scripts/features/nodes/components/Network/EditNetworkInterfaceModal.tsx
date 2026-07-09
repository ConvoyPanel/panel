import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import useQueryMutator from '@/hooks/use-query-mutator.ts'

import {
    networkInterfaceSchema,
    updateNetworkInterface,
    networkInterfaceQueries,
} from '@/features/nodes/network-interfaces/api.ts'

import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'

const EditNetworkInterfaceModal = () => {
    const { nodeId } = NetworkRoute.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const [networkInterface, open, close] = useNetworkInterfacesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

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
        if (!networkInterface) return

        form.reset({
            name: networkInterface.name,
            description: networkInterface.description ?? '',
            isVlanAware: networkInterface.isVlanAware,
            vlanTag: networkInterface.vlanTag ?? '',
        })
    }, [networkInterface])

    useEffect(() => {
        if (!isVlanAware) {
            form.setValue('vlanTag', '')
        }
    }, [form, isVlanAware])

    const submit = async (data: z.infer<typeof networkInterfaceSchema>) => {
        try {
            const updatedInterface = await updateNetworkInterface(
                Number(nodeId),
                networkInterface!.id,
                data
            )

            await mutate(data => {
                if (!data) return

                return data.map(item => {
                    if (item.id === updatedInterface.id) {
                        return updatedInterface
                    }
                    return item
                })
            }, false)

            close('edit')
            toast.success('Network interface updated')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={open => !open && close('edit')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>
                        Editing {networkInterface?.name}
                    </CredenzaTitle>
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
                            <FormButton>Save</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default EditNetworkInterfaceModal
