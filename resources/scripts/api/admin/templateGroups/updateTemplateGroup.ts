import { z } from 'zod';
import axios from '@/lib/axios';
import { templateGroupSchema } from '@/api/admin/templateGroups/createTemplateGroup.ts';
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts';

const updateTemplateGroup = async (
    uuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof templateGroupSchema>
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