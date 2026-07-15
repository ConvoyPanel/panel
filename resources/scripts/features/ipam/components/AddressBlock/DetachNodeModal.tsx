import useAsyncFunction from '@/hooks/use-async-function.ts'
import { toast } from 'sonner'
import { detachNode } from '@/features/ipam/api.ts'
import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'
import { Node } from '@/types/node.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import { Mutator } from '@/types/query.ts'

interface Props {
    mutate: Mutator<PaginatedNetworkInterfaces>
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
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Detach {node?.displayName || 'Node'}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to detach this node from the address block group?
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className="mt-4">
                    <ResponsiveDialogClose
                        render={
                            <Button variant="outline">Cancel</Button>
                        }
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant="destructive"
                        onClick={submit}
                    >
                        Detach
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DetachNodeModal
