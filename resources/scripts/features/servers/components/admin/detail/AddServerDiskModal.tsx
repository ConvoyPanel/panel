import StoragePicker from '@/features/servers/components/admin/Create/pickers/StoragePicker'
import { useAddServerDisk } from '@/features/servers/disks/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
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

const AddServerDiskModal = ({
    serverId,
    nodeId,
    open,
    onOpenChange,
}: Props) => {
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
            toast.add({ title: 'Disk added', type: 'success' })
            onOpenChange(false)
        } catch (e) {
            const handled = handleFormErrors(e, form.setError, {
                storage_id: 'storageId',
                size: 'size',
            })
            if (!handled)
                toast.add({ title: 'Failed to add disk', type: 'error' })
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Add a disk</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as never)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
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
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Add disk</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AddServerDiskModal
