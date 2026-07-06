import { useQuery } from '@tanstack/react-query'

import getQrCode from '@/api/account/authenticator/getQrCode.ts'

export const getKey = () => ['account.authenticator.qr-code']

const useQrCode = () => {
    return useQuery({ queryKey: getKey(), queryFn: getQrCode })
}

export default useQrCode
