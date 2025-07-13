import { Template } from '@/types/template.ts';

export const rawDataToTemplate = (raw: any): Template => ({
    uuid: raw.uuid,
    templateGroupId: raw.template_group_id,
    name: raw.name,
    description: raw.description,
    vmid: raw.vmid,
    isAdminOnly: Boolean(raw.is_admin_only),
});