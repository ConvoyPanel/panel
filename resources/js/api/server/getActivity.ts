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

export default (serverId: number) => {
    return axios.get<PaginatedInterface<Activity[]>>(route('servers.show.activity', serverId))
}