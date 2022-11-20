import { PaginatedResult } from '@/api/http';
import { ReactNode, useEffect } from 'react';
import { Pagination as MantinePagination } from '@mantine/core'

interface RenderFuncProps<T> {
    items: T[];
    isLastPage: boolean;
    isFirstPage: boolean;
}

interface Props<T> {
    data: PaginatedResult<T>;
    showGoToLast?: boolean;
    showGoToFirst?: boolean;
    onPageSelect: (page: number) => void;
    children: (props: RenderFuncProps<T>) => ReactNode;
}

const Pagination = <T,>({ data: { items, pagination }, onPageSelect, children }: Props<T>) => {
    const isFirstPage = pagination.currentPage === 1;
    const isLastPage = pagination.currentPage >= pagination.totalPages;

    const pages = [];

    // Start two spaces before the current page. If that puts us before the starting page default
    // to the first page as the starting point.
    const start = Math.max(pagination.currentPage - 2, 1);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 5);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    useEffect(() => {
        if (pagination.currentPage > pagination.totalPages) {
            onPageSelect(pagination.totalPages);
        }
    }, [])

    useEffect(() => {
        // Don't use react-router to handle changing this part of the URL, otherwise it
        // triggers a needless re-render. We just want to track this in the URL incase the
        // user refreshes the page.
        window.history.replaceState(
          null,
          document.title,
          `/${pagination.currentPage <= 1 ? '' : `?page=${pagination.currentPage}`}`
        )
      }, [pagination])

    return (
        <>
            {children({ items, isFirstPage, isLastPage })}
            {pages.length > 1 && (
                <div className='flex justify-end mt-6'>
                    <MantinePagination onChange={onPageSelect} page={pagination.currentPage} total={pagination.totalPages}/>
                </div>
            )}
        </>
    );
}

export default Pagination