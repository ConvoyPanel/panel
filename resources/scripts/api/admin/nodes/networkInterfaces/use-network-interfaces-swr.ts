import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'
import getNetworkInterfaces from '@/api/admin/nodes/networkInterfaces/getNetworkInterfaces.ts'

export const getKey = (nodeId: number) => ['node.network-interfaces', nodeId]

const useNetworkInterfacesSWR = () => {
    const {nodeId} = useParams({ strict: false }) as { nodeId: number }

    return useSWR(getKey(nodeId), () => getNetworkInterfaces(nodeId))
}

export default useNetworkInterfacesSWR