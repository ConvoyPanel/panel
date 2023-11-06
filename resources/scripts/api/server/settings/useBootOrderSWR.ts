import useSWR, { SWRConfiguration } from 'swr'

import getBootOrder, {
    BootOrderSettings,
} from '@/api/server/settings/getBootOrder'

const useBootOrderSWR = (serverUuid: string, config?: SWRConfiguration) => {
    return useSWR<BootOrderSettings>(
        ['server:settings:hardware:boot-order', serverUuid],
        () => getBootOrder(serverUuid),
        config
    )
}

export default useBootOrderSWR