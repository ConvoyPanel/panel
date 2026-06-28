import useSWR from '@/lib/swr'
import { TemplateGroup } from '@/types/template-group.ts'
import { rawDataToTemplateGroup } from '@/api/transformers/template-group.ts'

const useTemplateGroupsSWR = (uuid?: string) => {
    return useSWR<TemplateGroup[]>(
        uuid ? `/api/client/servers/${uuid}/settings/template-groups` : null,
        async (url: string) => {
            const { data } = await import('@/lib/axios.ts').then(m => m.default.get(url))
            return data.data.map(rawDataToTemplateGroup)
        }
    )
}

export default useTemplateGroupsSWR


