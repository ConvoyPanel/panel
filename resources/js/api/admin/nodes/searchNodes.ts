import { Node } from '@/api/admin/nodes/types'
import axios from 'axios'

export default (search: string) => {
  return axios.get<Node[]>(
    route('admin.nodes.search', {
      params: {
        'filter[*]' : search,
      }
    })
  )
}
