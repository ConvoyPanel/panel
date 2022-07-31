import axios from 'axios'

export default (archive: string, serverId: number) => {
  return axios.delete(route('servers.backups', { archive, server: serverId }))
}