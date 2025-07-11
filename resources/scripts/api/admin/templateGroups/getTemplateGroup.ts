import axios from '@/lib/axios';
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts';
import { TemplateGroup } from '@/types/template-group';

const getTemplateGroup = async (id: number): Promise<TemplateGroup> => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/template-groups/${id}`);

    return rawDataToTemplateGroup(data);
}

export default getTemplateGroup;