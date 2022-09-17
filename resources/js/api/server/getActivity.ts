import { PaginatedInterface } from '@/api/types/default'
import axios from 'axios'

export type Status = 'ok' | 'error' | 'running'

export interface Activity {
  id: number
  batch: string
  status: Status
  event: string
  ip?: string
  description?: string
  properties?: string
  created_at: string
  updated_at: string
}

export interface Parameters {
  event?: string
  batch?: string
  status?: Status
}

export default ({ event, batch, status }: Parameters, serverId: number) => {
  return axios.get<PaginatedInterface<Activity[]>>(
    route('servers.show.activity', {
      server: serverId,
    }),
    {
      params: {
        'filter[event]': event,
        'filter[batch]': batch,
        'filter[status]': status,
      },
    }
  )
}
