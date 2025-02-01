import { PaginatedResult } from '@/utils/http.ts'
import { TableOptions } from '@tanstack/react-table'
import { ReactNode } from 'react'

export interface DataTableProps<TData>
    extends Omit<
        TableOptions<TData>,
        | 'data'
        | 'state'
        | 'pageCount'
        | 'getCoreRowModel'
        | 'manualFiltering'
        | 'manualPagination'
        | 'manualSorting'
    > {
    data: TData[] | PaginatedResult<TData> | null | undefined

    /**
     * Defines filter fields for the table. Supports both dynamic faceted filters and search filters.
     * - Faceted filters are rendered when `options` are provided for a filter field.
     * - Otherwise, search filters are rendered.
     *
     * The indie filter field `value` represents the corresponding column name in the database table.
     * @default []
     * @type { label: string, value: keyof TData, placeholder?: string, options?: { label: string, value: string, icon?: React.ComponentType<{ className?: string }> }[] }[]
     * @example
     * ```ts
     * // Render a search filter
     * const filterFields = [
     *   { label: "Title", value: "title", placeholder: "Search titles" }
     * ];
     * // Render a faceted filter
     * const filterFields = [
     *   {
     *     label: "Status",
     *     value: "status",
     *     options: [
     *       { label: "Todo", value: "todo" },
     *       { label: "In Progress", value: "in-progress" },
     *     ]
     *   }
     * ];
     * ```
     */
    filterFields?: DataTableFilterField<TData>[]

    skeletonRows?: number
    toolbar?: boolean
    searchable?: boolean
    query?: string
    setQuery?: (query: string) => void
    paginated?: boolean
    page?: number
    setPage?: (page: number) => void
    perPage?: number
    setPerPage?: (perPage: number) => void
    perPageOptions?: number[]
    showPerPageOptions?: boolean

    rightActions?: ReactNode
}

export interface DataTableFilterField<TData> {
    id: Extract<keyof TData, string>
    label: string
    placeholder?: string
    options?: Option[]
}

export interface Option {
    label: string
    value: string
    icon?: React.ComponentType<{ className?: string }>
    count?: number
}
