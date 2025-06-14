import createModalStore from '@/hooks/create-modal-store.ts'
import { Address } from '@/types/address.ts'

export const useAddressModal = createModalStore<Address, 'edit' | 'delete'>()