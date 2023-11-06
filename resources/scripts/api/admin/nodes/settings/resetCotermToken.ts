import http from '@/api/http'

const resetCotermToken = async (nodeId: number): Promise<string> => {
    const {
        data: { data },
    } = await http.post(
        `/api/admin/nodes/${nodeId}/settings/reset-coterm-token`
    )

    return data.token
}

export default resetCotermToken
