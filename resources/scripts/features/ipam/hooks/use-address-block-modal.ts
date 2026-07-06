import createModalStore from '@/hooks/create-modal-store.ts'
import { AddressBlock } from '@/types/address-block.ts'

export const useAddressBlockModal = createModalStore<AddressBlock, 'edit' | 'delete'>()