import { TemplateGroup, TemplateIcon } from '@/types/template-group.ts'
import { rawDataToTemplate } from '@/api/transformers/template.ts';

export const rawDataToTemplateGroup = (raw: any): TemplateGroup => ({
    uuid: raw.uuid,
    name: raw.name,
    description: raw.description,
    icon: raw.icon as TemplateIcon,
    isAdminOnly: Boolean(raw.is_admin_only),
    templates: raw.templates?.data?.map(rawDataToTemplate),
});