import axios from 'axios'
import { Server as DefaultServer } from '@/api/server/types'
import { PaginatedInterface } from '@/api/types/default'

export interface Server extends DefaultServer {
  node: {
    id: number
    name: string
  }
}

export default (search: string) => {
  return axios.get<PaginatedInterface<Server[]>>(
    route('admin.servers.search', {
      params: {
        'filter[*]': search,
      },
    })
  )
}
