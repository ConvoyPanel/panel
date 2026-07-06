import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import {
    TemplateQueryParams,
    default as getTemplates,
} from '@/api/admin/templateGroups/templates/getTemplates.ts'

export const templateQueries = {
    all: (templateGroupUuid: string | null | undefined) =>
        ['admin', 'template-groups', templateGroupUuid, 'templates'] as const,
    list: (
        templateGroupUuid: string | null | undefined,
        params: TemplateQueryParams
    ) =>
        queryOptions({
            queryKey: [
                ...templateQueries.all(templateGroupUuid),
                'list',
                params,
            ] as const,
            queryFn: () => getTemplates(templateGroupUuid!, params),
            enabled: !!templateGroupUuid,
            placeholderData: keepPreviousData,
        }),
}

const useTemplates = (
    templateGroupUuid: string | null | undefined,
    params: TemplateQueryParams
) => useQuery(templateQueries.list(templateGroupUuid, params))

export default useTemplates
