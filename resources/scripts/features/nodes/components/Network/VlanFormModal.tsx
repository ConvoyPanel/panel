import useVlansModalStore from '@/features/nodes/hooks/use-vlans-modal-store.ts'
import {
    createVlan,
    networkInterfaceQueries,
    updateVlan,
    upsertVlan,
    vlanSchema,
    withVlans,
} from '@/features/nodes/network-interfaces/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route as NetworkRoute } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

/**
 * Declaring and editing share one form: the fields are identical, and the only
 * difference is whether the tag is already spoken for. Declaring a tag that
 * servers already carry is a normal thing to do — it names a VLAN that was
 * previously only implied — so nothing here treats it as a conflict.
 */
const VlanFormModal = ({ mode }: { mode: 'create' | 'edit' }) => {
    const { nodeId } = NetworkRoute.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const { open, data, close } = useModal(useVlansModalStore, mode)

    const networkInterface = data?.networkInterface
    const vlan = data?.vlan ?? null

    const form = useForm({
        resolver: zodResolver(vlanSchema),
        defaultValues: { tag: '', name: '', description: '' },
    })

    useEffect(() => {
        if (!open) return

        form.reset({
            // An undeclared VLAN opens this form with its tag prefilled: the
            // point is to name the tag already in use, not to pick a new one.
            tag: vlan?.tag ?? '',
            name: vlan?.name ?? '',
            description: vlan?.description ?? '',
        })
    }, [open, vlan?.id, vlan?.tag])

    const submit = async (values: z.infer<typeof vlanSchema>) => {
        if (!networkInterface) return

        try {
            const saved =
                mode === 'edit' && vlan?.id != null
                    ? await updateVlan(
                          Number(nodeId),
                          networkInterface.id,
                          vlan.id,
                          values
                      )
                    : await createVlan(
                          Number(nodeId),
                          networkInterface.id,
                          values
                      )

            await mutate(
                withVlans(networkInterface.id, upsertVlan(saved)),
                false
            )

            close()
            toast.success(mode === 'edit' ? 'VLAN updated' : 'VLAN declared')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {mode === 'edit'
                            ? `Editing VLAN ${vlan?.tag}`
                            : 'Declare VLAN'}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {mode === 'edit'
                            ? `On ${networkInterface?.name}. Renaming a VLAN does not change any server's tag.`
                            : `On ${networkInterface?.name}. Servers already carrying this tag will be listed under it.`}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'space-y-2'}>
                            <InputForm
                                name={'tag'}
                                label={'VLAN tag'}
                                type={'number'}
                                min={1}
                                max={4094}
                                autoComplete={'off'}
                            />
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                description={
                                    'Optional. Shown instead of the bare tag.'
                                }
                                autoComplete={'off'}
                            />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'}>Cancel</Button>
                                }
                            />
                            <FormButton>
                                {mode === 'edit' ? 'Save' : 'Declare'}
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default VlanFormModal
