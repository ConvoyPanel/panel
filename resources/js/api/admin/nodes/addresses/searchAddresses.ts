import { Address } from '@/api/admin/nodes/addresses/types'
import axios from 'axios'

export default (query: string, nodeId: number, showAvailableIps: boolean = false) => {
  return axios.get<Address[]>(route('admin.nodes.show.addresses.search', nodeId), {
    params: { 'filter[*]': query, show_available_ips: showAvailableIps },
  })
}
