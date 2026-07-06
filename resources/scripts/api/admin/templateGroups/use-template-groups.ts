import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getTemplateGroups, {
    TemplateGroupQueryParams,
} from '@/api/admin/templateGroups/getTemplateGroups.ts'

export const getKey = (params: TemplateGroupQueryParams) => [
    'template-groups',
    params,
]

const useTemplateGroups = (params: TemplateGroupQueryParams) => {
    return useQuery({
        queryKey: getKey(params),
        queryFn: () => getTemplateGroups(params),
        placeholderData: keepPreviousData,
    })
}

export default useTemplateGroups
