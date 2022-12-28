import { Dispatch, SetStateAction, useState } from 'react'
import { useLocation } from 'react-router-dom'

const usePagination = () => {
    const { search: location } = useLocation()
    const defaultPage = Number(new URLSearchParams(location).get('page') || '1')
    const [page, setPage] = useState(
      !isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1
    )

    return [page, setPage] as [number, Dispatch<SetStateAction<number>>]
}

export default usePagination