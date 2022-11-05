import http, { getPaginationSet, PaginatedResult } from '@/api/http'
import { rawDataToServerObject, Server } from '@/api/server/getServer'

interface QueryParams {
  query?: string
  page?: number
  type?: string
  perPage?: number
}

export default ({
  query,
  perPage = 50,
  ...params
}: QueryParams): Promise<PaginatedResult<Server>> => {
  return new Promise((resolve, reject) => {
    http
      .get('/api/client/servers', {
        params: {
          'filter[name]': query,
          per_page: perPage,
          ...params,
        },
      })
      .then(({ data }) =>
        resolve({
          items: (data.data || []).map((datum: any) =>
            rawDataToServerObject(datum)
          ),
          pagination: getPaginationSet(data.meta.pagination),
        })
      )
      .catch(reject)
  })
}
