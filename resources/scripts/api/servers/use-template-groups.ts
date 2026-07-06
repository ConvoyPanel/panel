import { useQuery } from '@tanstack/react-query'

import { serverQueries } from '@/api/servers/use-server.ts'

const useTemplateGroups = (uuid?: string) =>
    useQuery(serverQueries.templateGroups(uuid))

export default useTemplateGroups
