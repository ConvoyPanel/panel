import { keepPreviousData, useQuery } from '@tanstack/react-query'

import {
    TemplateQueryParams,
    default as getTemplates,
} from '@/api/admin/templateGroups/templates/getTemplates.ts'

export const getKey = (
    templateGroupUuid: string | null | undefined,
    params: TemplateQueryParams
) => ['templates', templateGroupUuid, params]

const useTemplates = (
    templateGroupUuid: string | null | undefined,
    params: TemplateQueryParams
) => {
    return useQuery({
        queryKey: getKey(templateGroupUuid, params),
        queryFn: () => getTemplates(templateGroupUuid!, params),
        enabled: !!templateGroupUuid,
        placeholderData: keepPreviousData,
    })
}

export default useTemplates
