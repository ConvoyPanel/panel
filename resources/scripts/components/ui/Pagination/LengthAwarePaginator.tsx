import { PaginatedResult } from '@/utils/http.ts'
import { usePagination } from '@mantine/hooks'
import { useNavigate } from '@tanstack/react-router'
import { ReactNode, useEffect, useRef } from 'react'

import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/Pagination/index.ts'

interface Props<T> {
    page: number
    data: PaginatedResult<T> | null | undefined
    onPageChange: (page: number) => void
    children: (props: ChildProps<T>) => ReactNode
}

interface ChildProps<T> {
    items: T[]
}

const LengthAwarePaginator = <T,>({
    page: currentPage,
    data,
    onPageChange,
    children,
}: Props<T>) => {
    const navigate = useNavigate()
    const cachedTotalPages = useRef(1)

    useEffect(() => {
        if (data !== null && data !== undefined) {
            cachedTotalPages.current = data.pagination.totalPages
        }
    }, [data])

    const pagination = usePagination({
        total: cachedTotalPages.current,
        page: currentPage,
        onChange: onPageChange,
    })

    return (
        <>
            {data && children({ items: data.items })}
            <Pagination>
                <PaginationContent>
                    <PaginationPrevious
                        onClick={() =>
                            navigate({ search: { page: currentPage - 1 } })
                        }
                        disabled={currentPage <= 1}
                        role='button'
                    />
                    {pagination.range.map((page, index) => {
                        if (page === 'dots') {
                            return <PaginationEllipsis key={index} />
                        }

                        return (
                            <PaginationItem key={index}>
                                <PaginationLink
                                    isActive={currentPage === page}
                                    onClick={() =>
                                        navigate({ search: { page } })
                                    }
                                >
                                    {page}
                                </PaginationLink>
                            </PaginationItem>
                        )
                    })}
                    <PaginationNext
                        onClick={() =>
                            navigate({ search: { page: currentPage + 1 } })
                        }
                        disabled={currentPage >= cachedTotalPages.current}
                    />
                </PaginationContent>
            </Pagination>
        </>
    )
}

export default LengthAwarePaginator
