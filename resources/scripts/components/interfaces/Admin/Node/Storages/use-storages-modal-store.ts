import createModalStore from '@/hooks/create-modal-store.ts'
import { NodeStorage } from '@/types/storage.ts'

const useStoragesModalStore = createModalStore<
    NodeStorage,
    'show' | 'edit' | 'delete'
>()

export default useStoragesModalStore
