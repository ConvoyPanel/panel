import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getTemplateGroups, {
    TemplateGroupQueryParams,
} from '@/api/admin/templateGroups/getTemplateGroups.ts'

export const templateGroupQueries = {
    all: () => ['admin', 'template-groups'] as const,
    lists: () => [...templateGroupQueries.all(), 'list'] as const,
    list: (params: TemplateGroupQueryParams) =>
        queryOptions({
            queryKey: [...templateGroupQueries.lists(), params] as const,
            queryFn: () => getTemplateGroups(params),
            placeholderData: keepPreviousData,
        }),
}

const useTemplateGroups = (params: TemplateGroupQueryParams) =>
    useQuery(templateGroupQueries.list(params))

export default useTemplateGroups
