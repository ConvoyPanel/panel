import { z } from 'zod';
import axios from '@/lib/axios';
import { rawDataToTemplate } from '@/api/transformers/template.ts';
import { templateSchema } from '@/api/admin/templateGroups/templates/createTemplate.ts';

const updateTemplate = async (
    templateGroupUuid: string,
    templateUuid: string,
    { isAdminOnly, ...rest }: z.infer<typeof templateSchema>
) => {
    const {
        data: { data },
    } = await axios.put(
        `/api/admin/template-groups/${templateGroupUuid}/templates/${templateUuid}`,
        {
            ...rest,
            is_admin_only: isAdminOnly,
        }
    );

    return rawDataToTemplate(data);
};

export default updateTemplate;