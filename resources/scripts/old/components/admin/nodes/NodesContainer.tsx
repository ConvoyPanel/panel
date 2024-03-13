import { bytesToString } from '@/util/helpers'
import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import deleteNode from '@/api/admin/nodes/deleteNode'
import { Node, NodeResponse } from '@/api/admin/nodes/getNodes'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'

import Menu from '@/components/elements/Menu'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Table, {
    Actions,
    ColumnArray,
    RowActionsProps,
} from '@/components/elements/displays/Table'

import SearchBar from '@/components/admin/SearchBar'
import CreateNodeModal from '@/components/admin/nodes/CreateNodeModal'


const NodesContainer = () => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.index')
    const [page, setPage] = usePagination()
    const [open, setOpen] = useState(false)
    const { clearFlashes, clearAndAddHttpError } =
        useFlashKey('admin.nodes.index')

    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, mutate } = useNodesSWR({ page, query: debouncedQuery })

    const columns: ColumnArray<Node> = [
        {
            accessor: 'name',
            header: tStrings('node'),
            cell: ({ value, row }) => (
                <Link
                    to={`/admin/nodes/${row.id}`}
                    className='link text-foreground'
                >
                    {value}
                </Link>
            ),
        },
        {
            accessor: 'fqdn',
            header: tStrings('fqdn'),
        },
        {
            accessor: 'memory',
            header: tStrings('memory'),
            cell: ({ value }) => bytesToString(value),
        },
        {
            accessor: 'disk',
            header: tStrings('disk'),
            cell: ({ value }) => bytesToString(value),
        },
        {
            accessor: 'serversCount',
            header: tStrings('server_other'),
            align: 'center',
        },
    ]

    const rowActions = ({ row: node }: RowActionsProps<Node>) => {
        const handleDelete = async () => {
            clearFlashes()

            try {
                await deleteNode(node.id)

                mutate(
                    mutateData =>
                        ({
                            ...mutateData,
                            items: mutateData!.items.filter(
                                n => n.id !== node.id
                            ),
                        }) as NodeResponse,
                    false
                )
            } catch (error) {
                clearAndAddHttpError(error as Error)
            }
        }

        return (
            <Actions>
                <Menu.Item
                    onClick={() => navigator.clipboard.writeText(node.id)}
                >
                    Copy ID
                </Menu.Item>
                <Menu.Divider />
                <Menu.Item
                    color='red'
                    disabled={node.serversCount > 0}
                    onClick={handleDelete}
                >
                    {tStrings('delete')}
                </Menu.Item>
            </Actions>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock
                title={tStrings('node_other') ?? 'Nodes'}
                showFlashKey='admin.nodes.index'
            >
                <CreateNodeModal open={open} onClose={() => setOpen(false)} />
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={t('create_node')}
                    onClick={() => setOpen(true)}
                />
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
            </PageContentBlock>
        </div>
    )
}

export default NodesContainer