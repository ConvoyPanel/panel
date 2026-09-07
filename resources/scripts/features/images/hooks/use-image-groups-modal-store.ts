import createModalStore from '@/hooks/create-modal-store.ts'
import { ImageGroup } from '@/types/image.ts'

const useImageGroupsModalStore = createModalStore<
    ImageGroup,
    'edit' | 'delete' | 'show'
>()

export default useImageGroupsModalStore
