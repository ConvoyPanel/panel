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
                : { ...obj, [`filter[${key}]`]: value }
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
