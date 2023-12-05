/*
MIT License

Pterodactyl®
Copyright © Dane Everitt <dane@daneeveritt.com> and contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
import {completeNavigationProgress, resetNavigationProgress, startNavigationProgress} from '@mantine/nprogress';
import axios, {AxiosInstance} from 'axios';


const http: AxiosInstance = axios.create({
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

http.interceptors.request.use(req => {
    if (!req.url?.endsWith('/state')) {
        resetNavigationProgress()
        startNavigationProgress()
    }

    return req
})

http.interceptors.response.use(
    resp => {
        if (!resp.request?.url?.endsWith('/state')) {
            completeNavigationProgress()
        }

        return resp
    },
    error => {
        completeNavigationProgress()

        throw error
    }
)

export default http

/**
 * Converts an error into a human readable response. Mostly just a generic helper to
 * make sure we display the message from the server back to the user if we can.
 */
export function httpErrorToHuman(error: any): string {
    if (error.response && error.response.data) {
        let {data} = error.response

        // Some non-JSON requests can still return the error as a JSON block. In those cases, attempt
        // to parse it into JSON so we can display an actual error.
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data)
            } catch (e) {
                // do nothing, bad json
            }
        }

        if (data.message) {
            return data.message
        }

        // Errors from wings directory, mostly just for file uploads.
        if (data.error && typeof data.error === 'string') {
            return data.error
        }
    }

    return error.message
}

export interface FractalResponseData {
    [k: string]: any
}

export interface FractalResponseList {
    data: FractalResponseData[]
}

export interface FractalPaginatedResponse extends FractalResponseList {
    meta: {
        pagination: {
            total: number
            count: number
            /* eslint-disable camelcase */
            per_page: number
            current_page: number
            total_pages: number
            /* eslint-enable camelcase */
        }
    }
}

export interface PaginatedResult<T> {
    items: T[]
    pagination: PaginationDataSet
}

export interface PaginationDataSet {
    total: number
    count: number
    perPage: number
    currentPage: number
    totalPages: number
}

export function getPaginationSet(data: any): PaginationDataSet {
    return {
        total: data.total,
        count: data.count,
        perPage: data.per_page,
        currentPage: data.current_page,
        totalPages: data.total_pages,
    }
}

type QueryBuilderFilterValue = string | number | boolean | null

export interface QueryBuilderParams<
    FilterKeys extends string = string,
    SortKeys extends string = string,
> {
    page?: number
    filters?: {
        [K in FilterKeys]?:
        | QueryBuilderFilterValue
        | Readonly<QueryBuilderFilterValue[]>
    }
    sorts?: {
        [K in SortKeys]?: -1 | 0 | 1 | 'asc' | 'desc' | null
    }
}

/**
 * Helper function that parses a data object provided and builds query parameters
 * for the Laravel Query Builder package automatically. This will apply sorts and
 * filters deterministically based on the provided values.
 */
export const withQueryBuilderParams = (
    data?: QueryBuilderParams
): Record<string, unknown> => {
    if (!data) return {}

    const filters = Object.keys(data.filters || {}).reduce(
        (obj, key) => {
            const value = data.filters?.[key]

            return !value || value === ''
                ? obj
                : {...obj, [`filter[${key}]`]: value}
        },
        {} as NonNullable<QueryBuilderParams['filters']>
    )

    const sorts = Object.keys(data.sorts || {}).reduce((arr, key) => {
        const value = data.sorts?.[key]
        if (!value || !['asc', 'desc', 1, -1].includes(value)) {
            return arr
        }

        return [...arr, (value === -1 || value === 'desc' ? '-' : '') + key]
    }, [] as string[])

    return {
        ...filters,
        sort: !sorts.length ? undefined : sorts.join(','),
        page: data.page,
    }
}