import useAsyncFunction from '@/hooks/use-async-function.ts'
import { toast } from 'sonner'
import detachNode from '@/api/admin/addressBlockGroups/detachNode.ts'
import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'
import { Node } from '@/types/node.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import { KeyedMutator } from 'swr'

interface Props {
    mutate: KeyedMutator<PaginatedNetworkInterfaces>
    node: Node | null
    open: boolean
    onOpenChange: (open: boolean) => void
}

const DetachNodeModal = ({ mutate, node, open, onOpenChange }: Props) => {
    const { addressBlockGroupId } = Route.useParams()

    const [state, submit] = useAsyncFunction(async () => {
        try {
            if (!node) return

            await detachNode(Number(addressBlockGroupId), node.id)
            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: data.items.filter(item => item.nodeId !== node.id),
                }
            }, false)
            toast.success('Node detached successfully')
            onOpenChange(false)
        } catch (e) {
            toast.error('Failed to detach node')
            throw e
        }
    })

    return (
        <Credenza open={open} onOpenChange={onOpenChange}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Detach {node?.displayName || 'Node'}</CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to detach this node from the address block group?
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaFooter className="mt-4">
                    <CredenzaClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </CredenzaClose>
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant="destructive"
                        onClick={submit}
                    >
                        Detach
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DetachNodeModal
