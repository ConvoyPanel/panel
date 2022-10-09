import axios from 'axios'
import { Server as DefaultServer } from '@/api/server/types'
import { PaginatedInterface } from '@/api/types/default'

export interface Server extends DefaultServer {
  node: {
    id: number
    name: string
  }
}

export default (search: string, nodeId?: number) => {
  return axios.get<PaginatedInterface<Server[]>>(
    route('admin.servers.search', {
      'filter[uuidShort]': search,
      'filter[name]': search,
      'filter[vmid]': search,
      ...(nodeId && { 'filter[node.id]': nodeId }),
    })
  )
}
