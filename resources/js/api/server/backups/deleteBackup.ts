import axios from 'axios'

export default (archive: string, serverId: number) => {
  return axios.delete(route('servers.show.backups.destroy', { archive, server: serverId }))
}