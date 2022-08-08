import axios from 'axios'
/* import {
  faPlay,
  faClock,
  faStop,
  faExclamationCircle,
} from '@fortawesome/free-solid-svg-icons' */

export const refreshTime: number = 2000 // milliseconds

export interface FormattedBytes {
  size: number
  unit: string
}

export type ServerState = 'querying' | 'stopped' | 'running' | 'error'
export type ColorType =
  | 'primary'
  | 'secondary'
  | 'success'
  | 'danger'
  | 'warning'
  | 'info'
  | 'light'
  | 'dark'
  | 'neutral'

export interface iconStateInterface {
  [key: string]: ColorType
}

export const colorState: iconStateInterface = {
  querying: 'neutral',
  stopped: 'danger',
  running: 'success',
  error: 'danger',
}

export type Sizes = 'B' | 'KB' | 'MB' | 'GB' | 'TB' | 'PB' | 'EB' | 'ZB' | 'YB'

export function formatBytes(
  bytes: number,
  decimals = 2,
  customSize?: Sizes
): FormattedBytes {
  if (bytes === 0) return { size: 0, unit: 'B' }

  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
  const dm = decimals < 0 ? 0 : decimals

  const i = customSize ? sizes.indexOf(customSize) : Math.floor(Math.log(bytes) / Math.log(k))
  const size = parseFloat((bytes / Math.pow(k, i)).toFixed(dm))

  return {
    size,
    unit: sizes[i],
  }
}

export interface ServerStateInterface {
  netout: number
  netin: number
  name: string
  cpu: number
  mem: number
  disk: number
  uptime: number
  status: ServerState
  ha: {
    managed: number
  }
  diskwrite: number
  diskread: number
  cpus: number
  qmpstatus: string
  vmid: number
  maxdisk: number
  serial: number
  maxmem: number
}

export default (id: number) => {
  return axios.get<ServerStateInterface>(route('servers.show.status', id))
}
