import { Optimistic } from '@/lib/swr'
import { useParams } from 'react-router-dom'
import useSWR, { Key, SWRResponse } from 'swr'

import getCoterm from '@/api/admin/coterms/getCoterm'
import { Coterm } from '@/api/admin/coterms/getCoterms'


export const getKey = (id: number): Key => ['admin.coterms', id]

const useCotermSWR = () => {
    const { cotermId } = useParams()
    const id = parseInt(cotermId!)

    return useSWR<Coterm>(getKey(id), () => getCoterm(id), {
        revalidateOnMount: false,
    }) as Optimistic<SWRResponse<Coterm, any>>
}

export default useCotermSWR