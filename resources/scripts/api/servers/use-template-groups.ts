import { useQuery } from '@tanstack/react-query'
import { TemplateGroup } from '@/types/template-group.ts'
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts'

export const getKey = (uuid: string | undefined) => ['server.template-groups', uuid]

const useTemplateGroups = (uuid?: string) => {
    return useQuery<TemplateGroup[]>({
        queryKey: getKey(uuid),
        queryFn: async () => {
            const { data } = await import('@/lib/axios.ts').then(m =>
                m.default.get(`/api/client/servers/${uuid}/settings/template-groups`)
            )
            return data.data.map(rawDataToTemplateGroup)
        },
        enabled: !!uuid,
    })
}

export default useTemplateGroups
