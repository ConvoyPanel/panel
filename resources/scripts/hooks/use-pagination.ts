import { parseAsInteger, useQueryState } from 'nuqs'
import { useDebouncedValue } from '@mantine/hooks'

const usePagination = () => {
    const [query, setQuery] = useQueryState('query', { defaultValue: '' })
    const [debouncedQuery] = useDebouncedValue(query, 300)
    const [page, setPage] = useQueryState('page', parseAsInteger.withDefault(1))
    const [perPage, setPerPage] = useQueryState('perPage', parseAsInteger.withDefault(20))

    return {
        query,
        debouncedQuery,
        setQuery,
        page,
        setPage,
        perPage,
        setPerPage,
    }
}

export default usePagination
