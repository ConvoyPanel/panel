import useSWR from '@/lib/swr'

import getQrCode from '@/api/account/authenticator/getQrCode.ts'

export const getKey = () => 'account.authenticator.qr-code'

const useQrCodeSWR = () => {
    return useSWR(getKey(), getQrCode)
}

export default useQrCodeSWR
