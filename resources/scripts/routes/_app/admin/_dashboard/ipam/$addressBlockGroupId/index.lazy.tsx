import { useAddressBlockGroup } from '@/features/ipam/api.ts'
import AddressBlockTab from '@/features/ipam/components/AddressBlock/AddressBlockTab.tsx'
import AttachedNodesTab from '@/features/ipam/components/AddressBlock/AttachedNodesTab.tsx'
import AddressPoolCard from '@/features/ipam/components/AddressPoolCard.tsx'
import EditBlockGroupModal from '@/features/ipam/components/EditBlockGroupModal.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId/'
)({
    component: GroupBlocks,
})

function GroupBlocks() {
    const { data: group, refetch } = useAddressBlockGroup()

    return (
        <>
            <Heading>{group?.name}</Heading>
            <AddressPoolCard group={group} />
            <Tabs defaultValue={'addressBlocks'}>
                <TabsList>
                    <TabsTrigger value={'addressBlocks'}>
                        IP Blocks
                        {/* The count belongs on the tab: switching to a tab to find out how many
                            things are behind it is the trip the label can save. Plain text, not a
                            Badge — a bordered pill is taller than the trigger's own text line and
                            pushes the whole h-8 TabsList out of shape around it. The trigger's
                            `gap-1.5` does the spacing. */}
                        {group && (
                            <span
                                className={
                                    'text-muted-foreground text-xs tabular-nums'
                                }
                            >
                                {group.addressBlocksCount}
                            </span>
                        )}
                    </TabsTrigger>
                    <TabsTrigger value={'attachedNodes'}>
                        Attached Nodes
                        {group && (
                            <span
                                className={
                                    'text-muted-foreground text-xs tabular-nums'
                                }
                            >
                                {group.nodesCount}
                            </span>
                        )}
                    </TabsTrigger>
                </TabsList>
                <AddressBlockTab />
                <AttachedNodesTab />
            </Tabs>
            {/* The pool card's Edit opens this, so it mounts here rather than on the index the
                group list lives on. Delete deliberately stays on the index: removing the pool from
                the page that is about it would leave the reader on a route that no longer
                resolves. */}
            <EditBlockGroupModal mutate={async () => void (await refetch())} />
        </>
    )
}
