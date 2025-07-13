import { z } from 'zod';

import axios from '@/lib/axios';
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts';
import { TemplateIcon } from '@/types/template-group.ts'

export const templateGroupSchema = z.object({
    name: z.string().min(1).max(40),
    description: z.string().max(500),
    icon: z.preprocess(
        val => (val === '' ? null : val),
        z.nativeEnum(TemplateIcon).nullable()
    ),
    isAdminOnly: z.boolean(),
});

const createTemplateGroup = async (
    payload: z.infer<typeof templateGroupSchema>
) => {
    const { isAdminOnly, ...rest } = payload;
    const {
        data: { data },
    } = await axios.post('/api/admin/template-groups', {
        ...rest,
        is_admin_only: isAdminOnly,
    });

    return rawDataToTemplateGroup(data);
}

export default createTemplateGroup;