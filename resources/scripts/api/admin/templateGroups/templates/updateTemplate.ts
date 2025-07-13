import { z } from 'zod';
import axios from '@/lib/axios';
import { rawDataToTemplate } from '@/api/transformers/template.ts';
import { createTemplateSchema } from '@/api/admin/templateGroups/templates/createTemplate.ts';

const updateTemplate = async (
    templateGroupUuid: string,
    templateId: number,
    { isAdminOnly, ...rest }: z.infer<typeof createTemplateSchema>
) => {
    const {
        data: { data },
    } = await axios.put(
        `/api/admin/template-groups/${templateGroupUuid}/templates/${templateId}`,
        {
            ...rest,
            is_admin_only: isAdminOnly,
        }
    );

    return rawDataToTemplate(data);
};

export default updateTemplate;