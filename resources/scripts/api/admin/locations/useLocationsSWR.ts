import getLocations, { LocationResponse, QueryParams } from '@/api/admin/locations/getLocations'
import useSWR from 'swr'

const useLocationsSWR = ({page, query, ...params}: QueryParams) => {
    return useSWR<LocationResponse>(['admin:locations', page, query], () => getLocations({page, query, ...params}))
}

export default useLocationsSWR