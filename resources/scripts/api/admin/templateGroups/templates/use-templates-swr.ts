import useSWR from 'swr'

import {
    TemplateQueryParams,
    default as getTemplates,
} from '@/api/admin/templateGroups/templates/getTemplates.ts'

export const getKey = (
    templateGroupId: number,
    params: TemplateQueryParams
) => ['templates', templateGroupId, params]

const useTemplatesSWR = (
    templateGroupId: number,
    params: TemplateQueryParams
) => {
    return useSWR(getKey(templateGroupId, params), () =>
        getTemplates(templateGroupId, params)
    )
}

export default useTemplatesSWR
