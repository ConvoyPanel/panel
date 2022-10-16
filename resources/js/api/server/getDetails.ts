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
      monthly_total: number,
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
      ipv4: Pick<Address, 'cidr' | 'gateway' | 'address' | 'mac_address'>[]
      ipv6: Pick<Address, 'cidr' | 'gateway' | 'address' | 'mac_address'>[]
    }
    snapshot_limit?: number
    backup_limit?: number
    bandwidth_limit?: number
    mac_address?: string
  }
  config: {
    mac_address?: string
    boot_order: string[]
    disks: Disk[]
    template: boolean
    addresses: {
      ipv4?: Pick<Address, 'cidr' | 'gateway' | 'address'>
      ipv6?: Pick<Address, 'cidr' | 'gateway' | 'address'>
    }
  }
}

export default (id: number) => {
  return axios.get<Details>(route('servers.show.details', id))
}
