import { z } from 'zod';
import axios from '@/lib/axios';
import { createTemplateGroupSchema } from '@/api/admin/templateGroups/createTemplateGroup.ts';
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts';

const updateTemplateGroup = async (
    uuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof createTemplateGroupSchema>
) => {
    const {
        data: { data },
    } = await axios.put(`/api/admin/template-groups/${uuid}`, {
        ...rest,
        is_admin_only: isAdminOnly,
    });

    return rawDataToTemplateGroup(data);
};

export default updateTemplateGroup;