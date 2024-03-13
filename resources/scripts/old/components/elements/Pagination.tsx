import { Pagination as MantinePagination } from '@mantine/core'
import { ReactNode, useEffect } from 'react'

import { PaginatedResult } from '@/api/http'

interface RenderFuncProps<T> {
    items: T[]
    isLastPage: boolean
    isFirstPage: boolean
}

interface Props<T> {
    data: PaginatedResult<T>
    showGoToLast?: boolean
    showGoToFirst?: boolean
    onPageSelect: (page: number) => void
    children: (props: RenderFuncProps<T>) => ReactNode
}

const Pagination = <T,>({
    data: { items, pagination },
    onPageSelect,
    children,
}: Props<T>) => {
    const isFirstPage = pagination.currentPage === 1
    const isLastPage = pagination.currentPage >= pagination.totalPages

    const pages = []

    // Start two spaces before the current page. If that puts us before the starting page default
    // to the first page as the starting point.
    const start = Math.max(pagination.currentPage - 2, 1)
    const end = Math.min(pagination.totalPages, pagination.currentPage + 5)

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    useEffect(() => {
        if (pagination.currentPage > pagination.totalPages) {
            onPageSelect(pagination.totalPages)
        }
    }, [])

    useEffect(() => {
        const url = new URL(window.location.href)

        // don't add page param if it's the first page
        if (pagination.currentPage === 1) {
            const page = url.searchParams.get('page')
            if (page && parseInt(page) !== 1) {
                url.searchParams.delete('page')
                window.history.pushState({}, '', url.toString())
            }
            return
        }

        url.searchParams.set('page', pagination.currentPage.toString())
        window.history.pushState({}, '', url.toString())
    }, [pagination])

    return (
        <>
            {children({ items, isFirstPage, isLastPage })}
            {pages.length > 1 && (
                <div className='flex justify-end mt-3'>
                    <MantinePagination
                        onChange={onPageSelect}
                        page={pagination.currentPage}
                        total={pagination.totalPages}
                    />
                </div>
            )}
        </>
    )
}

export default Pagination
