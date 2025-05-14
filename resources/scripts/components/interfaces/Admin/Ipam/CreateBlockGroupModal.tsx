import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import createAddressBlockGroup, {
    addressBlockGroupSchema,
} from '@/api/admin/addressBlockGroups/createAddressBlockGroup.ts'
import { storageSchema } from '@/api/admin/nodes/storages/createStorage.ts'

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

interface Props {
    mutate: KeyedMutator<PaginatedAddressBlockGroups>
}

const CreateBlockGroupModal = ({ mutate }: Props) => {
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(addressBlockGroupSchema),
        defaultValues: {
            name: '',
            description: '',
        },
    })

    const submit = async (data: z.infer<typeof storageSchema>) => {
        try {
            const blockGroup = await createAddressBlockGroup(data)

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: [blockGroup, ...data.items],
                }
            })

            form.reset()
            setOpen(false)
            toast.success('Block group created')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button size={'sm'}>
                    <IconPlus className={'mr-2 size-4'} /> Add block group
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>New Block Group</CredenzaTitle>
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
                            <FormButton>Add block group</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateBlockGroupModal
