import http from '@/api/http'

const deleteAddress = (nodeId: number, addressId: number, syncNetworkConfig: boolean) =>
    http.delete(`/api/admin/nodes/${nodeId}/addresses/${addressId}`, {
        params: {
            sync_network_config: syncNetworkConfig,
        },
    })

export default deleteAddress
