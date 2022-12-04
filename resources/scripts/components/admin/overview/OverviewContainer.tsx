import Table from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import { ColumnDef, createColumnHelper } from '@tanstack/react-table'

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
    firstName: 'tanner',
    lastName: 'linsley',
    age: 24,
    visits: 100,
    status: 'relationship',
    progress: 50,
  },
  {
    firstName: 'tandy',
    lastName: 'miller',
    age: 40,
    visits: 40,
    status: 'relationship',
    progress: 80,
  },
  {
    firstName: 'joe',
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
      <p>overview</p>
      <Table columns={columns} data={defaultData}></Table>
    </PageContentBlock>
    </div>
  )
}

export default OverviewContainer
