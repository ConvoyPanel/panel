import {
    addressBlockGroupSchema,
    updateAddressBlockGroup,
} from '@/features/ipam/api.ts'
import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'
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

interface Props {
    mutate: Mutator<PaginatedAddressBlockGroups>
}

const EditBlockGroupModal = ({ mutate }: Props) => {
    const {
        open,
        data: blockGroup,
        close,
    } = useModal(useBlockGroupModalStore, 'edit')

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
        if (!blockGroup) return

        try {
            const updatedBlockGroup = await updateAddressBlockGroup(
                blockGroup.id,
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

            close()
            toast.add({ title: 'Block group updated', type: 'success' })
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
                        Editing {blockGroup?.name}
                    </ResponsiveDialogTitle>
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
