import createModalStore from '@/hooks/create-modal-store.ts';
import { TemplateGroup } from '@/types/template-group.ts';

const useTemplateGroupsModalStore = createModalStore<
    TemplateGroup,
    'edit' | 'delete' | 'show'
>();

export default useTemplateGroupsModalStore;