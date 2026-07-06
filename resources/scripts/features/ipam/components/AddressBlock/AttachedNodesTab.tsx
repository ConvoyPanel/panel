import { useState } from 'react'
import AttachNodeModal from '@/features/ipam/components/AddressBlock/AttachNodeModal.tsx'
import DetachNodeModal from '@/features/ipam/components/AddressBlock/DetachNodeModal.tsx'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route } from '@/routes/_app/admin/_dashboard/ipam/$addressBlockGroupId.tsx'
import {
    NetworkInterface,
    PaginatedNetworkInterfaces,
} from '@/types/network-interface.ts'
import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { Link } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import {
    useAttachedNodes,
    addressBlockGroupQueries,
} from '@/features/ipam/api.ts'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { TabsContent } from '@/components/ui/Tabs'

const AttachedNodesTab = () => {
    const { queryParams, tableProps } = useDataTable()
    const { addressBlockGroupId } = Route.useParams()
    const { data, isPlaceholderData } = useAttachedNodes(
        Number(addressBlockGroupId),
        queryParams
    )
    const mutate = useQueryMutator<PaginatedNetworkInterfaces>(
        addressBlockGroupQueries.nodes(Number(addressBlockGroupId), queryParams)
            .queryKey
    )

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
                isPlaceholderData={isPlaceholderData}
                rightActions={<AttachNodeModal mutate={mutate} />}
                {...tableProps}
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
