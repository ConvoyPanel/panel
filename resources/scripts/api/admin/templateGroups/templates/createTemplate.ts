import { z } from 'zod';
import axios from '@/lib/axios';
import { rawDataToTemplate } from '@/api/transformers/template.ts';

export const createTemplateSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(1000).nullable(),
    vmid: z.coerce.number().min(100).max(999999999),
    isAdminOnly: z.boolean(),
});

const createTemplate = async (
    templateGroupId: number,
    { isAdminOnly, ...rest }: z.infer<typeof createTemplateSchema>
) => {
    const {
        data: { data },
    } = await axios.post(
        `/api/admin/template-groups/${templateGroupId}/templates`,
        {
            ...rest,
            is_admin_only: isAdminOnly,
        }
    );

    return rawDataToTemplate(data);
};

export default createTemplate;