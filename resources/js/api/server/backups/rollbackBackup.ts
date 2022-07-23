import axios from 'axios'

export default (archive: string, serverId: number) => {
  return axios.post(route('servers.show.backups.rollback', serverId), {
    archive,
  })
}
