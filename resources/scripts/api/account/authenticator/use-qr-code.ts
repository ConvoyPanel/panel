import { useQuery } from '@tanstack/react-query'

import { authenticatorQueries } from '@/api/account/authenticator/use-is-authenticator-enabled.ts'

const useQrCode = () => useQuery(authenticatorQueries.qrCode())

export default useQrCode
