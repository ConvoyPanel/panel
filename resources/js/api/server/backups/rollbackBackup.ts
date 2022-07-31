import axios from 'axios'

export default (archive: string, serverId: number) => {
  return axios.post(route('servers.backups.rollback', serverId), {
    archive,
  })
}
