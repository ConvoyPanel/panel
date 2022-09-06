import { Address } from '@/api/admin/nodes/addresses/types'
import { ServerState } from '@/api/server/getStatus'
import axios from 'axios'

export interface Disk {
  disk: string
  size: string
}

export interface Details {
  vmid: number,
  status: ServerState,
  locked: boolean|string,
  usage: {
    uptime: number,
    network: {
      in: number,
      out: number,
    }
    disk: {
      read: number,
      write: number,
    }
  }
  limits: {
    cpu: number,
    memory: number,
    disk: number,
    addresses: {
      ipv4?: Pick<Address, 'cidr' | 'gateway' | 'address'>
      ipv6?: Pick<Address, 'cidr' | 'gateway' | 'address'>
    }
  }
  config: {
    boot_order: string[]
    disks: Disk[]
    template: boolean
    addresses: Pick<Address, 'cidr' | 'gateway' | 'address'>
  }
}

export default (id: number) => {
  return axios.get<Details>(route('servers.show.details', id))
}
