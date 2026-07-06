import { createLazyFileRoute } from '@tanstack/react-router'

import { useAddressBlockGroup } from '@/features/ipam/api.ts'

import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import { Heading } from '@/components/ui/Typography'
import AddressBlockTab from '@/features/ipam/components/AddressBlock/AddressBlockTab.tsx'
import AttachedNodesTab from '@/features/ipam/components/AddressBlock/AttachedNodesTab.tsx'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId/'
)({
    component: GroupBlocks,
})

function GroupBlocks() {
    const { data: group } = useAddressBlockGroup()

    return (
        <>
            <Heading>{group?.name}</Heading>
            <Tabs defaultValue={'addressBlocks'}>
                <TabsList>
                    <TabsTrigger value={'addressBlocks'}>IP Blocks</TabsTrigger>
                    <TabsTrigger value={'attachedNodes'}>
                        Attached Nodes
                    </TabsTrigger>
                </TabsList>
                <AddressBlockTab />
                <AttachedNodesTab />
            </Tabs>
        </>
    )
}
