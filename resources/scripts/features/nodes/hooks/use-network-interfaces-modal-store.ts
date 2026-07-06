import createModalStore from '@/hooks/create-modal-store.ts'
import { NetworkInterface } from '@/types/network-interface.ts'

const useNetworkInterfacesModalStore = createModalStore<
    NetworkInterface,
    'edit' | 'delete'
>()

export default useNetworkInterfacesModalStore
