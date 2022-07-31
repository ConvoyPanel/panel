import axios from 'axios'

export default (archive: string, serverId: number) => {
  return axios.delete(route('servers.show.backups', { archive, server: serverId }))
}