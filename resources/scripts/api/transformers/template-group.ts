import type { TemplateGroup } from '@/types/template-group.ts'
import { rawDataToTemplate } from '@/api/transformers/template.ts'

export const rawDataToTemplateGroup = (raw: any): TemplateGroup => ({
    ...(raw as TemplateGroup),
    templates: raw.templates?.map(rawDataToTemplate),
})
