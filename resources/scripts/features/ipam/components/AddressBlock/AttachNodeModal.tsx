import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { IconPlus } from '@tabler/icons-react'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { attachNode } from '@/features/ipam/api.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
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
import NodePicker from '@/features/servers/components/admin/Create/pickers/NodePicker.tsx'
import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker.tsx'
import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'

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
            await attachNode(Number(addressBlockGroupId), Number(data.networkInterfaceId))
            await mutate()
            form.reset()
            setOpen(false)
            toast.success('Node attached successfully')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to attach node')
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button>
                    <IconPlus className="size-4" /> Attach Node
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Attach Node</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <CredenzaBody className="space-y-4">
                            <NodePicker />
                            <NetworkInterfacePicker
                                nodeId={nodeId ? Number(nodeId) : null}
                            />
                        </CredenzaBody>
                        <CredenzaFooter className="mt-4">
                            <CredenzaClose asChild>
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Attach</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default AttachNodeModal
