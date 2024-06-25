import { useNavigate, useSearch } from '@tanstack/react-router'

const usePagination = () => {
    const search = useSearch({ strict: false })
    const navigate = useNavigate()

    // @ts-expect-error
    const page = search.page
    return { page, setPage: (page: number) => navigate({ search: { page } }) }
}

export default usePagination
