import { attachNode } from '@/features/ipam/api.ts'
import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker.tsx'
import NodePicker from '@/features/servers/components/admin/Create/pickers/NodePicker.tsx'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const schema = z.object({
    nodeId: z.string().min(1, 'A node is required'),
    networkInterfaceId: z.string().min(1, 'A network interface is required'),
})

interface Props {
    mutate: Mutator<PaginatedNetworkInterfaces>
}

const AttachNodeModal = ({ mutate }: Props) => {
    const { addressBlockGroupId } = Route.useParams()
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            nodeId: '',
            networkInterfaceId: '',
        },
    })

    const nodeId = form.watch('nodeId')

    const submit = async (data: z.infer<typeof schema>) => {
        try {
            await attachNode(
                Number(addressBlockGroupId),
                Number(data.networkInterfaceId)
            )
            await mutate()
            form.reset()
            setOpen(false)
            toast.add({ title: 'Node attached successfully', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to attach node', type: 'error' })
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className='size-4' /> Attach Node
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Attach Node</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className='space-y-4'>
                            <NodePicker />
                            <NetworkInterfacePicker
                                nodeId={nodeId ? Number(nodeId) : null}
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className='mt-4'>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant='outline' type='button'>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Attach</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AttachNodeModal
