import axios from '@/lib/axios.ts'

const retryInstallation = async (uuid: string) => {
    await axios.post(`/api/client/servers/${uuid}/retry-installation`)
}

export default retryInstallation

