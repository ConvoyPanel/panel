import axios from 'axios'

export default (archive: string, serverId: string) => {
  return axios.post(route('servers.show.backups.rollback', serverId), {
    archive,
  })
}
