import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { NodeContext } from '@/state/admin/node'
import usePagination from '@/util/usePagination'
import { ISO } from '@/api/admin/nodes/isos/getIsos'
import { bytesToString } from '@/util/helpers'
import Button from '@/components/elements/Button'
import { useState } from 'react'
import CreateIsoModal from '@/components/admin/nodes/isoLibrary/CreateIsoModal'

const columns: ColumnArray<ISO> = [
    {
        accessor: 'name',
        header: 'Name',
    },
    {
        accessor: 'uuid',
        header: 'UUID',
    },
    {
        accessor: 'fileName',
        header: 'File Name',
    },
    {
        accessor: 'size',
        header: 'Size',
        cell: ({ value }) => bytesToString(value),
    },
]

const IsoLibraryContainer = () => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [page, setPage] = usePagination()
    const { data, mutate } = useIsosSWR({ nodeId })
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock title='ISO Library' showFlashKey='admin:node:isos'>
                <CreateIsoModal open={open} onClose={() => setOpen(false)} />
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New ISO
                    </Button>
                </div>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} />}
                    </Pagination>
                )}
            </NodeContentBlock>
        </div>
    )
}

export default IsoLibraryContainer
