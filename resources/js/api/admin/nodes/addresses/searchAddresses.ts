import { Address } from '@/api/admin/nodes/addresses/types'
import { PaginatedInterface } from '@/api/types/default'
import axios from 'axios'

export default (
  query: string,
  nodeId: number,
  showAvailableIps: boolean = false
) => {
  return axios.get<PaginatedInterface<Address[]>>(
    route('admin.nodes.show.addresses.search', {
      node: nodeId,
      params: { 'filter[address]': query },
    }),
    {
      params: {
        show_available_ips: showAvailableIps,
      },
    }
  )
}
