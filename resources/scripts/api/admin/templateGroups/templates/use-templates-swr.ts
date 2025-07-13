import useSWR from 'swr'

import {
    TemplateQueryParams,
    default as getTemplates,
} from '@/api/admin/templateGroups/templates/getTemplates.ts'

export const getKey = (
    templateGroupUuid: string,
    params: TemplateQueryParams
) => ['templates', templateGroupUuid, params]

const useTemplatesSWR = (
    templateGroupUuid: string | null | undefined,
    params: TemplateQueryParams
) => {
    return useSWR(
        templateGroupUuid ? getKey(templateGroupUuid, params) : null,
        () => getTemplates(templateGroupUuid!, params)
    )
}

export default useTemplatesSWR
