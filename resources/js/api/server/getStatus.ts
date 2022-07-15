import axios from 'axios'
/* import {
  faPlay,
  faClock,
  faStop,
  faExclamationCircle,
} from '@fortawesome/free-solid-svg-icons' */

export const refreshTime: number = 2000 // milliseconds

export interface FormattedBytes {
  size: number;
  unit: string;
}

export type ServerState = 'querying' | 'stopped' | 'running' | 'error'

export interface iconStateInterface {
  [key: string]: {
    backgroundColor: string;
    textColor: string;
    //icon: any;
  }
}

export const iconState: iconStateInterface = {
  querying: {
    backgroundColor: 'bg-gray-200',
    textColor: 'text-gray-700',
    //icon: faClock,
  },
  stopped: {
    backgroundColor: 'bg-red-200',
    textColor: 'text-red-700',
    //icon: faStop,
  },
  running: {
    backgroundColor: 'bg-green-200',
    textColor: 'text-green-700',
    //icon: faPlay,
  },
  error: {
    backgroundColor: 'bg-gray-200',
    textColor: 'text-gray-700',
    //icon: faExclamationCircle,
  },
}

export function formatBytes(bytes: number, decimals = 2): FormattedBytes {
  if (bytes === 0) return { size: 0, unit: 'B' }

  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return {
    size: parseFloat((bytes / Math.pow(k, i)).toFixed(dm)),
    unit: sizes[i],
  }
}

export default (id: number) => {
  // @ts-ignore
  return axios.get(route('servers.show.status', id), {hideProgress: true})
}