import { PaginatedInterface, User } from '@/api/types/default'
import axios from 'axios'

export default (search: string) => {
  return axios.get<PaginatedInterface<User[]>>(
    route('admin.users.search', {
      params: {
        'filter[*]' : search,
      }
    })
  )
}
