import axios from 'axios'

export interface Resources {
  diskwrite: number
  netout: number
  maxdisk: number
  disk: number
  cpu: number
  template: number
  name: string
  uptime: number
  id: string
  maxmem: number
  diskread: number
  netin: number
  mem: number
  node: string
  vmid: number
  status: string
  type: string
  maxcpu: number
}

export default (id: number) => {
  return axios.get<Resources>(route('servers.show.get-resources', id))
}
