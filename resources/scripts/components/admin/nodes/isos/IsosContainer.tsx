import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import Table, { Actions, ColumnArray, RowActionsProps } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { NodeContext } from '@/state/admin/node'
import usePagination from '@/util/usePagination'
import { ISO, IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import { bytesToString } from '@/util/helpers'
import Button from '@/components/elements/Button'
import { useState } from 'react'
import CreateIsoModal from '@/components/admin/nodes/isos/CreateIsoModal'
import { Loader } from '@mantine/core'
import { ExclamationCircleIcon, EyeIcon, EyeSlashIcon } from '@heroicons/react/20/solid'
import Menu from '@/components/elements/Menu'
import deleteIso from '@/api/admin/nodes/isos/deleteIso'
import EditIsoModal from '@/components/admin/nodes/isos/EditIsoModal'
import { useTranslation } from 'react-i18next'

const columns: ColumnArray<ISO> = [
    {
        accessor: 'name',
        header: 'Name',
        cell: ({ value, row }) => (
            <div className='flex space-x-3 items-center'>
                <span>{value}</span>
                {row.completedAt === undefined ? (
                    <Loader size='sm' />
                ) : !row.isSuccessful ? (
                    <ExclamationCircleIcon className='h-5 w-5 text-error' />
                ) : null}
            </div>
        ),
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
        accessor: 'hidden',
        header: 'Visibility',
        cell: ({ value }) => (
            <div className='grid place-items-center'>
                {value ? (
                    <EyeSlashIcon title='hidden' className='h-5 w-5 text-foreground' />
                ) : (
                    <EyeIcon title='visible' className='h-5 w-5 text-foreground' />
                )}
            </div>
        ),
        align: 'center',
    },
    {
        accessor: 'size',
        header: 'Size',
        cell: ({ value }) => bytesToString(value),
    },
]

const IsosContainer = () => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [page, setPage] = usePagination()
    const { data, mutate } = useIsosSWR({ nodeId })
    const [showCreateModal, setShowCreateModal] = useState(false)
    const { t } = useTranslation('admin.nodes.isos')

    const rowActions = ({ row: iso }: RowActionsProps<ISO>) => {
        const [open, setOpen] = useState(false)

        const handleDelete = () => {
            deleteIso(nodeId, iso.uuid).then(() => {
                mutate(
                    mutateData =>
                        ({
                            ...mutateData,
                            items: mutateData!.items.filter(datum => datum.uuid !== iso.uuid),
                        } as IsoResponse),
                    false
                )
            })
        }
        return (
            <>
                <EditIsoModal open={open} onClose={() => setOpen(false)} iso={iso} />
                <Actions>
                    <Menu.Item onClick={() => setOpen(true)}>Edit</Menu.Item>
                    <Menu.Divider />
                    <Menu.Item color='red' onClick={handleDelete}>
                        Delete
                    </Menu.Item>
                </Actions>
            </>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock title='ISO Library'>
                <CreateIsoModal open={showCreateModal} onClose={() => setShowCreateModal(false)} mutate={mutate} />
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setShowCreateModal(true)} variant='filled'>
                        {t('create_iso')}
                    </Button>
                </div>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table rowActions={rowActions} columns={columns} data={items} />}
                    </Pagination>
                )}
            </NodeContentBlock>
        </div>
    )
}

export default IsosContainer
