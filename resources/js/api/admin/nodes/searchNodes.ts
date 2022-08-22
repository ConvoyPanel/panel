import { Node } from '@/api/admin/nodes/types'
import { PaginatedInterface } from '@/api/types/default'
import axios from 'axios'

export default (search: string) => {
  return axios.get<PaginatedInterface<Node[]>>(
    route('admin.nodes.search', {
      params: {
        'filter[*]' : search,
      }
    })
  )
}
