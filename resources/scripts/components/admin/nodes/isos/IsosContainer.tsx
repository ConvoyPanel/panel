import { bytesToString } from '@/util/helpers'
import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import {
    ExclamationCircleIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/react/20/solid'
import { Loader } from '@mantine/core'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import deleteIso from '@/api/admin/nodes/isos/deleteIso'
import { ISO, IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import Button from '@/components/elements/Button'
import ConfirmationModal from '@/components/elements/ConfirmationModal'
import Menu from '@/components/elements/Menu'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Table, {
    Actions,
    ColumnArray,
    RowActionsProps,
} from '@/components/elements/displays/Table'

import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import CreateIsoModal from '@/components/admin/nodes/isos/CreateIsoModal'
import EditIsoModal from '@/components/admin/nodes/isos/EditIsoModal'

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
                    <EyeSlashIcon
                        title='hidden'
                        className='h-5 w-5 text-foreground'
                    />
                ) : (
                    <EyeIcon
                        title='visible'
                        className='h-5 w-5 text-foreground'
                    />
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
    const { data: node } = useNodeSWR()
    const [page, setPage] = usePagination()
    const { data, mutate } = useIsosSWR({ nodeId: node.id })
    const [showCreateModal, setShowCreateModal] = useState(false)
    const [isoToDelete, setIsoToDelete] = useState<ISO | null>(null)
    const [confirmingDelete, setConfirmingDelete] = useState(false)
    const { t } = useTranslation('admin.nodes.isos')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.nodes.${node.id}.isos`
    )

    const confirmDelete = (iso: ISO) => {
        setIsoToDelete(iso)
        setConfirmingDelete(true)
    }

    const handleDelete = async () => {
        const iso = isoToDelete!
        clearFlashes()

        try {
            await deleteIso(node.id, iso.uuid)

            mutate(
                mutateData =>
                    ({
                        ...mutateData,
                        items: mutateData!.items.filter(
                            datum => datum.uuid !== iso.uuid
                        ),
                    }) as IsoResponse,
                false
            )
        } catch (error) {
            clearAndAddHttpError(error as Error)
        }
    }

    const rowActions = ({ row: iso }: RowActionsProps<ISO>) => {
        const [open, setOpen] = useState(false)

        return (
            <>
                <EditIsoModal
                    open={open}
                    onClose={() => setOpen(false)}
                    iso={iso}
                />
                <Actions>
                    <Menu.Item onClick={() => setOpen(true)}>Edit</Menu.Item>
                    <Menu.Divider />
                    <Menu.Item color='red' onClick={() => confirmDelete(iso)}>
                        Delete
                    </Menu.Item>
                </Actions>
            </>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock
                title='ISO Library'
                showFlashKey={`admin.nodes.${node.id}.isos`}
            >
                <ConfirmationModal
                    open={confirmingDelete}
                    title={`Delete ${isoToDelete?.name}?`}
                    description="Deleting this ISO removes it from the library and deletes its file from the Proxmox node. This can't be undone."
                    confirmLabel='Delete'
                    onConfirm={handleDelete}
                    onClose={() => setConfirmingDelete(false)}
                />
                <CreateIsoModal
                    open={showCreateModal}
                    onClose={() => setShowCreateModal(false)}
                    mutate={mutate}
                />
                <div className='flex justify-end items-center mb-3'>
                    <Button
                        onClick={() => setShowCreateModal(true)}
                        variant='filled'
                    >
                        {t('create_iso')}
                    </Button>
                </div>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => (
                            <Table
                                rowActions={rowActions}
                                columns={columns}
                                data={items}
                            />
                        )}
                    </Pagination>
                )}
            </NodeContentBlock>
        </div>
    )
}

export default IsosContainer
