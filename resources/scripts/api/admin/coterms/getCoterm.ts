import { rawDataToCoterm } from '@/api/admin/coterms/getCoterms'
import http from '@/api/http'


const getCoterm = async (id: number) => {
    const {
        data: { data },
    } = await http.get(`/api/admin/coterms/${id}`)

    return rawDataToCoterm(data)
}

export default getCoterm