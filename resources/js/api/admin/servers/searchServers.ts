import axios from 'axios'
import { Server as DefaultServer } from '@/api/server/types'

export interface Server extends DefaultServer {
  node: {
    id: number
    name: string
  }
}

export default (query: string) => {
  return axios.get<Server[]>(
    route('admin.servers.search', {
      query,
    })
  )
}
