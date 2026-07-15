import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Route } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { AxiosError } from 'axios'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import useQueryMutator from '@/hooks/use-query-mutator.ts'

import {
    deleteNetworkInterface,
    networkInterfaceQueries,
} from '@/features/nodes/network-interfaces/api.ts'

import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'

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

const DeleteNetworkInterfaceModal = () => {
    const { nodeId } = Route.useParams()
    const mutate = useQueryMutator<NetworkInterface[]>(
        networkInterfaceQueries.all(Number(nodeId))
    )
    const [networkInterface, open, close] = useNetworkInterfacesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'delete',
            state.closeModal,
        ])
    )

    const [state, submit] = useAsyncFunction(
        async (currentNetworkInterface: NetworkInterface) => {
            try {
                await deleteNetworkInterface(
                    Number(nodeId),
                    currentNetworkInterface.id
                )

                await mutate(data => {
                    if (!data) return data
                    return data.filter(
                        item => item.id !== currentNetworkInterface.id
                    )
                }, false)

                toast.success('Network interface deleted')
                close('delete')
            } catch (e) {
                let message = 'Deletion failed'

                if (e instanceof AxiosError && e.response?.data.message) {
                    message = e.response.data.message
                }

                toast.error(message)

                throw e
            }
        }
    )

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close('delete')}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {networkInterface?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Are you sure you want to delete this network interface?
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'}>Cancel</Button>
                        }
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(networkInterface!)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeleteNetworkInterfaceModal
