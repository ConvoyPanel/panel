import { useQuery } from '@tanstack/react-query'

import { authenticatorQueries } from '@/api/account/authenticator/use-is-authenticator-enabled.ts'

const useSecretKey = () => useQuery(authenticatorQueries.secretKey())

export default useSecretKey
