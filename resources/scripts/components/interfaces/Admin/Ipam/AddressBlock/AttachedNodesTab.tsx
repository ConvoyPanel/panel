import usePagination from '@/hooks/use-pagination.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'

import useAttachedNodesSWR from '@/api/admin/addressBlockGroups/use-attached-nodes-swr.ts'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { TabsContent } from '@/components/ui/Tabs'

const AttachedNodesTab = () => {
    const pagination = usePagination()
    const { addressBlockGroupId } = Route.useParams()
    const { data } = useAttachedNodesSWR(Number(addressBlockGroupId), {
        page: pagination.page,
        filters: {
            '*': pagination.debouncedQuery,
        },
    })

    const columns: ColumnDef<Node>[] = [
        {
            header: 'Name',
            accessorKey: 'displayName',
            enableHiding: false,
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to={`/admin/nodes/${cell.row.original.id}`}
                >
                    {cell.getValue<string>()}
                </Link>
            ),
        },
        {
            header: 'FQDN',
            accessorKey: 'fqdn',
            meta: {
                skeletonWidth: '7rem',
            },
        },
        {
            header: 'Memory',
            accessorKey: 'memory',
            meta: {
                skeletonWidth: '1rem',
            },
            cell: ({ cell }) => {
                const memory = byteSize(cell.getValue<number>(), {
                    units: 'iec',
                })

                return `${memory.value} ${memory.unit}`
            },
        },
    ]

    return (
        <TabsContent value={'attachedNodes'}>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                {...pagination}
            />
        </TabsContent>
    )
}

export default AttachedNodesTab