import { TemplateGroup } from '@/types/template-group.ts';
import { QueryBuilderParams, withQueryBuilderParams } from '@/utils/http';
import axios from '@/lib/axios';
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts';

export type TemplateGroupQueryParams = QueryBuilderParams<
    'name' | 'isAdminOnly'
>;

const getTemplateGroups = async (
    params: TemplateGroupQueryParams
): Promise<TemplateGroup[]> => {
    const { data } = await axios.get('/api/admin/template-groups', {
        params: withQueryBuilderParams(params),
    });

    return data.data.map(rawDataToTemplateGroup);
}

export default getTemplateGroups;