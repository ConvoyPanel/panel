import { rawDataToCoterm } from '@/api/admin/coterms/getCoterms'
import http from '@/api/http'

const resetCotermToken = async (id: number) => {
    const {
        data: { data },
    } = await http.post(`/api/admin/coterm/${id}/settings/reset-coterm-token`)

    return rawDataToCoterm(data)
}

export default resetCotermToken