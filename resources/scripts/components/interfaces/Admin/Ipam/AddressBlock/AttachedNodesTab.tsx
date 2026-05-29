import { useState } from 'react'
import AttachNodeModal from '@/components/interfaces/Admin/Ipam/AddressBlock/AttachNodeModal.tsx'
import DetachNodeModal from '@/components/interfaces/Admin/Ipam/AddressBlock/DetachNodeModal.tsx'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import usePagination from '@/hooks/use-pagination.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import { NetworkInterface } from '@/types/network-interface.ts'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import useAttachedNodesSWR from '@/api/admin/addressBlockGroups/use-attached-nodes-swr.ts'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { TabsContent } from '@/components/ui/Tabs'

const AttachedNodesTab = () => {
    const pagination = usePagination()
    const { addressBlockGroupId } = Route.useParams()
    const { data, mutate } = useAttachedNodesSWR(Number(addressBlockGroupId), {
        page: pagination.page,
        filters: {
            '*': pagination.debouncedQuery,
        },
    })

    const [selectedNode, setSelectedNode] = useState<Node | null>(null)

    const columns: ColumnDef<NetworkInterface>[] = [
        {
            header: 'Interface',
            accessorKey: 'name',
            meta: {
                skeletonWidth: '5rem',
            },
        },
        {
            header: 'Node',
            accessorKey: 'node.displayName',
            enableHiding: false,
            meta: {
                skeletonWidth: '5rem',
            },
            cell: ({ row }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to='/admin/nodes/$nodeId'
                    params={{ nodeId: String(row.original.node?.id) }}
                >
                    {row.original.node?.displayName}
                </Link>
            ),
        },
        {
            header: 'FQDN',
            accessorKey: 'node.fqdn',
            meta: {
                skeletonWidth: '7rem',
            },
        },
        actionsColumn(({ row }) => (
            <DropdownMenuItem
                onClick={() => setSelectedNode(row.original.node || null)}
            >
                Detach
            </DropdownMenuItem>
        )),
    ]

    return (
        <TabsContent value={'attachedNodes'}>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                rightActions={<AttachNodeModal mutate={mutate} />}
                {...pagination}
            />
            <DetachNodeModal
                mutate={mutate}
                node={selectedNode}
                open={!!selectedNode}
                onOpenChange={open => !open && setSelectedNode(null)}
            />
        </TabsContent>
    )
}

export default AttachedNodesTab
