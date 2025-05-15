import useAsyncFunction from '@/hooks/use-async-function.ts'
import { Route } from '@/routes/_app/admin/nodes.$nodeId/network.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { toast } from 'sonner'
import { useShallow } from 'zustand/react/shallow'

import deleteNetworkInterface from '@/api/admin/nodes/networkInterfaces/deleteNetworkInterface.ts'
import useNetworkInterfacesSWR from '@/api/admin/nodes/networkInterfaces/use-network-interfaces-swr.ts'

import useNetworkInterfacesModalStore from '@/components/interfaces/Admin/Node/Network/use-network-interfaces-modal-store.ts'

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

const DeleteNetworkInterfaceModal = () => {
    const { nodeId } = Route.useParams()
    const { mutate } = useNetworkInterfacesSWR()
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
                await deleteNetworkInterface(nodeId, currentNetworkInterface.id)

                await mutate(data => {
                    if (!data) return data
                    return data.filter(
                        item => item.id !== currentNetworkInterface.id
                    )
                }, false)

                toast.success('Network interface deleted')
                close('delete')
            } catch (e) {
                toast.error('Deletion failed')
                throw e
            }
        }
    )

    return (
        <Credenza open={open} onOpenChange={open => !open && close('delete')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>
                        Delete {networkInterface?.name}
                    </CredenzaTitle>
                    <CredenzaDescription>
                        Are you sure you want to delete this network interface?
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaFooter className={'mt-4'}>
                    <CredenzaClose asChild>
                        <Button variant={'outline'}>Cancel</Button>
                    </CredenzaClose>
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => submit(networkInterface!)}
                    >
                        Delete
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default DeleteNetworkInterfaceModal
