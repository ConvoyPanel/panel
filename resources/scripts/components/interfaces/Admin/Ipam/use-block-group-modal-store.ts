import createModalStore from '@/hooks/create-modal-store.ts'
import { AddressBlockGroup } from '@/types/address-block-group.ts'

const useBlockGroupModalStore = createModalStore<AddressBlockGroup, 'edit' | 'delete'>()

export default useBlockGroupModalStore