import { notFound } from '@tanstack/react-router'
import { AxiosError } from 'axios'
import { FieldValues, UseFormSetError } from 'react-hook-form'


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
        perPage: data.perPage,
        currentPage: data.currentPage,
        totalPages: data.totalPages,
    }
}

type QueryBuilderFilterValue = string | number | boolean | null

export interface QueryBuilderParams<
    FilterKeys extends string = string,
    SortKeys extends string = string,
> {
    page?: number
    perPage?: number
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
        /* eslint-disable-next-line camelcase */
        per_page: data.perPage,
    }
}

export const processAxiosError = (error: AxiosError) => {
    if (error.response?.status === 404) {
        throw notFound()
    }
}

interface ValidationErrorResponse {
    message: string
    errors: Record<string, string[]>
}

interface ErrorWithResponse {
    response: {
        status: number
        data: ValidationErrorResponse
    }
}

/**
 * Converts snake_case string to camelCase.
 */
const toCamelCase = (str: string): string =>
    str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase())

/**
 * Handles form validation errors by mapping snake_case fields to camelCase and setting errors.
 *
 * @param error - The error object to handle.
 * @param setError - The `setError` function from `react-hook-form`.
 * @param customMapping - An optional object to provide custom field name mappings.
 * @returns A boolean indicating if the error was handled.
 */
export const handleFormErrors = <T extends FieldValues>(
    error: unknown,
    setError: UseFormSetError<T>,
    customMapping: Record<string, string> = {}
): boolean => {
    if (
        error &&
        typeof error === 'object' &&
        'response' in error &&
        (error as ErrorWithResponse).response?.status === 422
    ) {
        const responseData = (error as ErrorWithResponse).response.data

        if (responseData && responseData.errors) {
            for (const [field, messages] of Object.entries(responseData.errors)) {
                if (messages.length > 0) {
                    // Use custom mapping if available, otherwise convert to camelCase
                    const mappedField = customMapping[field] || toCamelCase(field)

                    // @ts-ignore
                    setError(mappedField as keyof T, {
                        type: 'manual',
                        message: messages[0],
                    })
                }
            }
            return true // Indicate that the error was handled
        }
    }

    return false // Indicate that the error was not handled
}