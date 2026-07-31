import createModalStore from '@/hooks/create-modal-store.ts'
import { NetworkInterface, Vlan } from '@/types/network-interface.ts'

/**
 * A VLAN is only addressable through its bridge, so every modal in this family
 * carries the interface as well as the row. `vlan` is null when declaring a new
 * one, and an undeclared VLAN (`vlan.id === null`) can only reach the create
 * modal — there is nothing yet to edit or delete.
 */
export interface VlanModalData {
    networkInterface: NetworkInterface
    vlan: Vlan | null
}

const useVlansModalStore = createModalStore<
    VlanModalData,
    'create' | 'edit' | 'delete'
>()

export default useVlansModalStore
