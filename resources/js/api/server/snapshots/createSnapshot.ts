import axios from 'axios'

export default (name: string, serverId: number) => {
  return axios.post(
    route('servers.show.snapshots.store', { server: serverId }),
    { name }
  )
}
