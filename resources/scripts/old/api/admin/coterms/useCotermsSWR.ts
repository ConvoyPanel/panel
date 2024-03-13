import useSWR from 'swr'



import getCoterms, { CotermResponse, QueryParams } from "@/api/admin/coterms/getCoterms";


const useCotermsSWR = ({ page, query, ...params }: QueryParams) => {
    return useSWR<CotermResponse>(['admin.coterms', page, query], () => getCoterms({ page, query, ...params }))
}

export default useCotermsSWR