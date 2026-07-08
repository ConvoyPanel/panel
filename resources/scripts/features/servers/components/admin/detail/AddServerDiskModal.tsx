import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import { useAddServerDisk } from '@/features/servers/disks/api.ts'
import { handleFormErrors } from '@/utils/http.ts'

import StoragePicker from '@/features/servers/components/admin/Create/pickers/StoragePicker'

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
import { InputForm } from '@/components/ui/Forms'

const addDiskSchema = z.object({
    storageId: z.string({ error: 'Storage is required.' }).min(1),
    // GiB — PVE allocates data disks in whole GiB.
    size: z.coerce.number().min(1),
})

type AddDiskSchema = z.infer<typeof addDiskSchema>

interface Props {
    serverId: number
    nodeId: number
    open: boolean
    onOpenChange: (open: boolean) => void
}

const AddServerDiskModal = ({ serverId, nodeId, open, onOpenChange }: Props) => {
    const add = useAddServerDisk(serverId)

    const form = useForm<z.input<typeof addDiskSchema>>({
        resolver: zodResolver(addDiskSchema),
        defaultValues: { storageId: '', size: 10 },
    })

    useEffect(() => {
        if (open) form.reset({ storageId: '', size: 10 })
    }, [open])

    const submit = async (data: AddDiskSchema) => {
        try {
            await add.mutateAsync({
                storageId: Number(data.storageId),
                size: data.size * 1024 * 1024 * 1024,
            })
            toast.success('Disk added')
            onOpenChange(false)
        } catch (e) {
            const handled = handleFormErrors(e, form.setError, {
                storage_id: 'storageId',
                size: 'size',
            })
            if (!handled) toast.error('Failed to add disk')
        }
    }

    return (
        <Credenza open={open} onOpenChange={onOpenChange}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Add a disk</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as never)}>
                        <CredenzaBody className={'space-y-4'}>
                            <StoragePicker
                                nodeId={nodeId}
                                requiredContentTypes={['storesKvm']}
                            />
                            <InputForm
                                name={'size'}
                                label={'Size (GiB)'}
                                type={'number'}
                                min={1}
                            />
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Add disk</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default AddServerDiskModal
