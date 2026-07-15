import byteSize from 'byte-size'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import {
    type ServerDisk,
    useResizeServerDisk,
} from '@/features/servers/disks/api.ts'
import { handleFormErrors } from '@/utils/http.ts'

import { Button } from '@/components/ui/Button'
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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'

const BYTES_PER_GIB = 1024 * 1024 * 1024

const resizeDiskSchema = z.object({
    // GiB — grow only. The exact "larger than current" check is enforced by the
    // backend (`cannot_shrink_disk`); here we just require a positive integer.
    size: z.coerce.number().min(1),
})

type ResizeDiskSchema = z.infer<typeof resizeDiskSchema>

interface Props {
    serverId: number
    disk: ServerDisk | null
    onOpenChange: (open: boolean) => void
}

const ResizeServerDiskModal = ({ serverId, disk, onOpenChange }: Props) => {
    const resize = useResizeServerDisk(serverId)

    const currentGib = disk ? Math.ceil(disk.size / BYTES_PER_GIB) : 0

    const form = useForm<z.input<typeof resizeDiskSchema>>({
        resolver: zodResolver(resizeDiskSchema),
        defaultValues: { size: currentGib },
    })

    useEffect(() => {
        if (disk) form.reset({ size: Math.ceil(disk.size / BYTES_PER_GIB) })
    }, [disk])

    const submit = async (data: ResizeDiskSchema) => {
        if (!disk) return

        try {
            await resize.mutateAsync({
                diskId: disk.id,
                size: data.size * BYTES_PER_GIB,
            })
            toast.success('Disk resized')
            onOpenChange(false)
        } catch (e) {
            const handled = handleFormErrors(e, form.setError, { size: 'size' })
            if (!handled) toast.error('Failed to resize disk')
        }
    }

    return (
        <ResponsiveDialog open={disk !== null} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Resize disk</ResponsiveDialogTitle>
                    {disk && (
                        <ResponsiveDialogDescription>
                            {disk.interface ?? 'This disk'} is currently{' '}
                            {byteSize(disk.size, { units: 'iec' }).toString()}.
                            Disks can only grow.
                        </ResponsiveDialogDescription>
                    )}
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as never)}>
                        <ResponsiveDialogBody>
                            <InputForm
                                name={'size'}
                                label={'New size (GiB)'}
                                type={'number'}
                                min={currentGib}
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
                            <FormButton>Resize</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ResizeServerDiskModal
