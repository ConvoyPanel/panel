import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { networkInterfaceSchema } from '@/api/admin/nodes/networkInterfaces/createNetworkInterface.ts'
import updateNetworkInterface from '@/api/admin/nodes/networkInterfaces/updateNetworkInterface.ts'
import useNetworkInterfacesSWR from '@/api/admin/nodes/networkInterfaces/use-network-interfaces-swr.ts'

import useNetworkInterfacesModalStore from '@/components/interfaces/Admin/Node/Network/use-network-interfaces-modal-store.ts'

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
import { InputForm, TextareaForm } from '@/components/ui/Forms'

const EditNetworkInterfaceModal = () => {
    const { nodeId } = NetworkRoute.useParams()
    const { mutate } = useNetworkInterfacesSWR()
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
        },
    })

    useEffect(() => {
        if (!networkInterface) return

        form.reset({
            name: networkInterface.name,
            description: networkInterface.description ?? '',
        })
    }, [networkInterface])

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
