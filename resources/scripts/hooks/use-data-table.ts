import { useDebouncedValue } from '@mantine/hooks'
import {
    ColumnFiltersState,
    RowSelectionState,
    SortingState,
    Updater,
    VisibilityState,
} from '@tanstack/react-table'
import { parseAsInteger, parseAsString, useQueryState } from 'nuqs'
import { useEffect, useMemo, useRef, useState } from 'react'

import { QueryBuilderParams } from '@/utils/http.ts'

interface UseDataTableOptions {
    /** Rows per page before the user changes it. @default 20 */
    defaultPerPage?: number
    /**
     * Filter key the search box maps to. Most lists use the catch-all `'*'`;
     * some (e.g. addresses) search a specific column. @default '*'
     */
    searchKey?: string
}

/**
 * Single source of truth for a server-driven `DataTable` (fetch-explicit model).
 *
 * Owns pagination/search/sort state in the URL (via `nuqs`) plus local
 * selection/visibility/column-filter state, and derives:
 *  - `queryParams` — a `QueryBuilderParams` object to feed the page's own query
 *    hook (page, perPage, `filter[*]` search + per-column filters, sort).
 *  - `tableProps` — the controllable state bundle to spread into `<DataTable>`.
 *
 * Changing search / perPage / sort / filters resets to page 1 and clears the
 * (page-scoped) row selection.
 */
const useDataTable = ({
    defaultPerPage = 20,
    searchKey = '*',
}: UseDataTableOptions = {}) => {
    const [query, setQuery] = useQueryState('query', { defaultValue: '' })
    const [debouncedQuery] = useDebouncedValue(query, 300)
    const [page, setPage] = useQueryState('page', parseAsInteger.withDefault(1))
    const [perPage, setPerPage] = useQueryState(
        'perPage',
        parseAsInteger.withDefault(defaultPerPage)
    )
    const [sort, setSort] = useQueryState('sort', parseAsString.withDefault(''))

    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({})
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {}
    )

    // Derive react-table SortingState from the single URL `sort` token, e.g.
    // "name" (asc) or "-name" (desc).
    const sorting: SortingState = useMemo(() => {
        if (!sort) return []
        const desc = sort.startsWith('-')
        return [{ id: desc ? sort.slice(1) : sort, desc }]
    }, [sort])

    const setSorting = (updater: Updater<SortingState>) => {
        const next =
            typeof updater === 'function' ? updater(sorting) : updater
        const first = next[0]
        void setSort(first ? (first.desc ? `-${first.id}` : first.id) : null)
    }

    // Reset to the first page (and drop the page-scoped selection) whenever the
    // result set changes shape.
    const prev = useRef({ debouncedQuery, perPage, sort, columnFilters })
    useEffect(() => {
        const p = prev.current
        if (
            p.debouncedQuery !== debouncedQuery ||
            p.perPage !== perPage ||
            p.sort !== sort ||
            p.columnFilters !== columnFilters
        ) {
            void setPage(1)
            setRowSelection({})
        }
        prev.current = { debouncedQuery, perPage, sort, columnFilters }
    }, [debouncedQuery, perPage, sort, columnFilters, setPage])

    const filters = useMemo(() => {
        const result: NonNullable<QueryBuilderParams['filters']> = {
            [searchKey]: debouncedQuery,
        }
        for (const { id, value } of columnFilters) {
            if (Array.isArray(value)) {
                if (value.length) result[id] = value as string[]
            } else if (value != null && value !== '') {
                result[id] = value as string
            }
        }
        return result
    }, [debouncedQuery, columnFilters, searchKey])

    const sorts = useMemo<NonNullable<QueryBuilderParams['sorts']>>(() => {
        const first = sorting[0]
        return first ? { [first.id]: first.desc ? 'desc' : 'asc' } : {}
    }, [sorting])

    const queryParams: QueryBuilderParams = {
        page,
        perPage,
        filters,
        sorts,
    }

    const tableProps = {
        query,
        setQuery,
        page,
        setPage,
        perPage,
        setPerPage,
        sorting,
        setSorting,
        columnFilters,
        setColumnFilters,
        rowSelection,
        setRowSelection,
        columnVisibility,
        setColumnVisibility,
    }

    return { queryParams, tableProps }
}

export default useDataTable
