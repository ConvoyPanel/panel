import { Dispatch, SetStateAction, useEffect, useState } from 'react'
import { useLocation } from 'react-router-dom'

const usePagination = () => {
    const { search: location } = useLocation()
    const defaultPage = Number(new URLSearchParams(location).get('page') || '1')
    const [page, setPage] = useState(
        !isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1
    )

    // watch for changes in the url and run setPage
    // if the page number is different in useEffect
    // then it will trigger a re-render
    useEffect(() => {
        const urlPage = Number(new URLSearchParams(location).get('page') || '1')
        if (!isNaN(urlPage) && urlPage > 0 && urlPage !== page) {
            setPage(urlPage)
        }
    }, [location])

    return [page, setPage] as [number, Dispatch<SetStateAction<number>>]
}

export default usePagination