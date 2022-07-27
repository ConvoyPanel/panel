import { Node } from '@/api/admin/nodes/types'
import axios from 'axios'

export default (query: string) => {
  return axios.get<Node[]>(
    route('admin.nodes.search', {
      query,
    })
  )
}
