import useVlansModalStore from '@/features/nodes/hooks/use-vlans-modal-store.ts'
import {
    deleteVlan,
    networkInterfaceQueries,
    removeVlan,
    withVlans,
} from '@/features/nodes/network-interfaces/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface, Vlan } from '@/types/network-interface.ts'
import { AxiosError } from 'axios'

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
import { toast } from '@/components/ui/Toast'

const DeleteVlanModal = () => {
    const { nodeId } = Route.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const { open, data, close } = useModal(useVlansModalStore, 'delete')

    const networkInterface = data?.networkInterface
    const vlan = data?.vlan ?? null

    const [state, submit] = useAsyncFunction(
        async (interfaceId: number, target: Vlan) => {
            if (target.id == null) return

            try {
                await deleteVlan(Number(nodeId), interfaceId, target.id)

                await mutate(withVlans(interfaceId, removeVlan(target)), false)

                toast.add({ title: 'VLAN deleted', type: 'success' })
                close()
            } catch (e) {
                let message = 'Deletion failed'

                if (e instanceof AxiosError && e.response?.data.message) {
                    message = e.response.data.message
                }

                toast.add({ title: message, type: 'error' })

                throw e
            }
        }
    )

    const servers = vlan?.serversCount ?? 0
    const inUse = servers > 0
    const subject =
        servers === 1 ? 'The 1 server keeps' : `All ${servers} servers keep`

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete VLAN {vlan?.tag}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {inUse
                            ? /* Deliberately not framed as a warning about
                                 losing connectivity: the tag lives on the
                                 servers, so nothing about their networking
                                 changes. Only the name goes away. */
                              `This only removes the name. ${subject} tag ${vlan?.tag}, and the VLAN stays listed as undeclared.`
                            : 'Nothing is using this VLAN. It will disappear from the list.'}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() =>
                            networkInterface &&
                            vlan &&
                            submit(networkInterface.id, vlan)
                        }
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteVlanModal
