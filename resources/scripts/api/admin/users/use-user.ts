import { useQuery } from '@tanstack/react-query'

import { userQueries } from '@/api/admin/users/use-users.ts'

const useUser = (id: number | null) => useQuery(userQueries.detail(id))

export default useUser
