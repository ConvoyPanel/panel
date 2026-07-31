import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'
import {
    networkInterfaceQueries,
    networkInterfaceSchema,
    updateNetworkInterface,
} from '@/features/nodes/network-interfaces/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, TextareaForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const EditNetworkInterfaceModal = () => {
    const { nodeId } = NetworkRoute.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const {
        open,
        data: networkInterface,
        close,
    } = useModal(useNetworkInterfacesModalStore, 'edit')

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
        if (!networkInterface) return

        try {
            const updatedInterface = await updateNetworkInterface(
                Number(nodeId),
                networkInterface.id,
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

            close()
            toast.add({ title: 'Network interface updated', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Editing {networkInterface?.name}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'space-y-2'}>
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
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditNetworkInterfaceModal
