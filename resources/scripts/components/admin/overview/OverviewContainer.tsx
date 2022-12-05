import Table from '@/components/elements/displays/Table'
import Checkbox from '@/components/elements/inputs/Checkbox'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Scroller from '@/components/elements/Scroller'
import { ColumnDef, createColumnHelper } from '@tanstack/react-table'
import { useEffect, useState } from 'react'

type Person = {
  firstName: string
  lastName: string
  age: number
  visits: number
  progress: number
  status: 'relationship' | 'complicated' | 'single'
  subRows?: Person[]
}

const columns = [
  {
    header: 'Name',
    accessor: 'firstName',
  }
]

const defaultData: Person[] = [
  {
    firstName: 'Mrs. Anush K',
    lastName: 'linsley',
    age: 24,
    visits: 100,
    status: 'relationship',
    progress: 50,
  },
  {
    firstName: 'Mrs. Whogivsachit',
    lastName: 'miller',
    age: 40,
    visits: 40,
    status: 'relationship',
    progress: 80,
  },
  {
    firstName: 'joe mama adfs asdf asdf adf adfaf dafd sadf sfad sf dadf safd sa dfsa faf dsfds adfs a dfsadf safd afd ',
    lastName: 'dirte',
    age: 45,
    visits: 20,
    status: 'relationship',
    progress: 10,
  },
]

const OverviewContainer = () => {
  return (
    <div className='bg-background h-full'>
    <PageContentBlock title='Overview' showFlashKey='admin:overview'>
      <Table selectable columns={columns} data={defaultData} headerActions={() => <></>}></Table>
    </PageContentBlock>
    </div>
  )
}

export default OverviewContainer
