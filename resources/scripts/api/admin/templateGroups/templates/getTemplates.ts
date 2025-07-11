import { Template } from '@/types/template.ts';
import { QueryBuilderParams, withQueryBuilderParams } from '@/utils/http';
import axios from '@/lib/axios';
import { rawDataToTemplate } from '@/api/transformers/template.ts';

export type TemplateQueryParams = QueryBuilderParams<
    'name' | 'isAdminOnly'
>;

const getTemplates = async (
    templateGroupId: number,
    params: TemplateQueryParams
): Promise<Template[]> => {
    const { data } = await axios.get(`/api/admin/template-groups/${templateGroupId}/templates`, {
        params: withQueryBuilderParams(params),
    });

    return data.data.map(rawDataToTemplate);
}

export default getTemplates;