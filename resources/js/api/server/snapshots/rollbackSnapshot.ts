import axios from 'axios'

export default (name: string, serverId: number) => {
  return axios.post(
    route('servers.snapshots.rollback', { server: serverId }),
    { name }
  )
}
