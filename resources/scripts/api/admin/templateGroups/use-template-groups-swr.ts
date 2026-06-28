import useSWR from '@/lib/swr';

import getTemplateGroups, {
    TemplateGroupQueryParams,
} from '@/api/admin/templateGroups/getTemplateGroups.ts';

export const getKey = (params: TemplateGroupQueryParams) => [
    'template-groups',
    params,
];

const useTemplateGroupsSWR = (params: TemplateGroupQueryParams) => {
    return useSWR(getKey(params), () => getTemplateGroups(params));
}

export default useTemplateGroupsSWR;