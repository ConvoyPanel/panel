import getLocations, { LocationResponse, QueryParams } from '@/api/admin/locations/getLocations'
import useSWR from 'swr'

const useLocationsSWR = ({page, ...params}: QueryParams) => {
    return useSWR<LocationResponse>(['admin:locations', page], () => getLocations({page, ...params}))
}

export default useLocationsSWR