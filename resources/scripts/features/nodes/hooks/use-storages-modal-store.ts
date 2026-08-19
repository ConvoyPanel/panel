import createModalStore from '@/hooks/create-modal-store.ts'
import { NodeStorage } from '@/features/nodes/types.ts'

const useStoragesModalStore = createModalStore<
    NodeStorage,
    'edit' | 'delete'
>()

export default useStoragesModalStore
