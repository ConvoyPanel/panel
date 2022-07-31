import axios from 'axios'

export interface Logs {
  total: number
  data?: Log[]
}

export interface Log {
  pstart: number
  id: string
  user: string
  upid: string
  status: string
  type: string
  pid: number
  node: string
  starttime: number
  endtime: number
}

export default (serverId: number) => {
  return axios.get(route('servers.show.logs.json', serverId))
}
