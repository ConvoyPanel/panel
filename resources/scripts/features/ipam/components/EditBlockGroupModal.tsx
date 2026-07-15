import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import {
    addressBlockGroupSchema,
    updateAddressBlockGroup,
} from '@/features/ipam/api.ts'

import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'

interface Props {
    mutate: Mutator<PaginatedAddressBlockGroups>
}

const EditBlockGroupModal = ({ mutate }: Props) => {
    const [blockGroup, open, close] = useBlockGroupModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

    const form = useForm({
        resolver: zodResolver(addressBlockGroupSchema),
        defaultValues: {
            name: '',
            description: '',
        },
    })

    useEffect(() => {
        if (!blockGroup) return

        form.reset({
            name: blockGroup.name,
            description: blockGroup.description || '',
        })
    }, [blockGroup])

    const submit = async (data: z.infer<typeof addressBlockGroupSchema>) => {
        try {
            const updatedBlockGroup = await updateAddressBlockGroup(
                blockGroup!.id,
                data
            )

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.map(item => {
                        if (item.id === updatedBlockGroup.id) {
                            return updatedBlockGroup
                        }
                        return item
                    }),
                }
            }, false)

            close('edit')
            toast.success('Block group updated')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close('edit')}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Editing {blockGroup?.name}</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'space-y-2'}>
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
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

export default EditBlockGroupModal
