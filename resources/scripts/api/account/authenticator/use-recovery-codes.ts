import { useQuery } from '@tanstack/react-query'

import { authenticatorQueries } from '@/api/account/authenticator/use-is-authenticator-enabled.ts'

const useRecoveryCodes = () => useQuery(authenticatorQueries.recoveryCodes())

export default useRecoveryCodes
