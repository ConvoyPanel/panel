import useSWR from 'swr'

import getTemplateGroups, {
    TemplateGroup,
} from '@/api/server/settings/getTemplateGroups'

const useTemplateGroupsSWR = (serverUuid: string) => {
    return useSWR<TemplateGroup[]>(
        ['server:settings:general:template-groups', serverUuid],
        () => getTemplateGroups(serverUuid)
    )
}

export default useTemplateGroupsSWR