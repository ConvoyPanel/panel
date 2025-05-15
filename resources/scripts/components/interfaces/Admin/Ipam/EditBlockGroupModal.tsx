import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { KeyedMutator } from 'swr'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { addressBlockGroupSchema } from '@/api/admin/addressBlockGroups/createAddressBlockGroup.ts'
import updateAddressBlockGroup from '@/api/admin/addressBlockGroups/updateAddressBlockGroup.ts'

import useBlockGroupModalStore from '@/components/interfaces/Admin/Ipam/use-block-group-modal-store.ts'

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

interface Props {
    mutate: KeyedMutator<PaginatedAddressBlockGroups>
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
            })

            close('edit')
            toast.success('Block group updated')
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
                    <CredenzaTitle>Editing {blockGroup?.name}</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody className={'space-y-2'}>
                            <InputForm name={'name'} label={'Name'} />
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

export default EditBlockGroupModal
