import { Inertia } from '@inertiajs/inertia'
import { Pagination } from '@mantine/core'
import { useEffect, useState } from 'react'

export interface Props {
    pages: number
    route: string
}

const Paginator = ({ pages, route: link }: Props) => {
    const [activePage, setActivePage] = useState(1)

    useEffect(() => {
      const page = new URLSearchParams(window.location.search).get('page')
      if (page) {
        setActivePage(parseInt(page))
      }
    }, [])

    const changePage = (page: number) => {
      Inertia.visit(route(link, { page  }))
    }

    return <div className='flex justify-end mt-3'>
    <Pagination
      page={activePage}
      total={pages}
      onChange={changePage}
      withEdges
    />
  </div>
}

export default Paginator