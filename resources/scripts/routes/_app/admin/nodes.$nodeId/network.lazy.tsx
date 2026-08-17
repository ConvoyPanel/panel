import CreateNetworkModal from '@/features/nodes/components/Network/CreateNetworkModal.tsx'
import DeleteNetworkInterfaceModal from '@/features/nodes/components/Network/DeleteNetworkInterfaceModal.tsx'
import DeleteVlanModal from '@/features/nodes/components/Network/DeleteVlanModal.tsx'
import EditNetworkInterfaceModal from '@/features/nodes/components/Network/EditNetworkInterfaceModal.tsx'
import NetworkInterfaceCard from '@/features/nodes/components/Network/NetworkInterfaceCard.tsx'
import VlanFormModal from '@/features/nodes/components/Network/VlanFormModal.tsx'
import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { IconWifiOff } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/network')({
    component: NodeNetwork,
})

function NodeNetwork() {
    const {
        data: interfaces,
        isLoading,
        isError,
        refetch,
    } = useNetworkInterfaces()

    return (
        <>
            <Heading>Network</Heading>
            {/* Actions sit in their own row under the heading, the same
                toolbar-then-content rhythm every admin index uses. */}
            {Boolean(interfaces?.length) && (
                <div className={'flex flex-wrap items-center gap-2'}>
                    <div className={'ml-auto flex items-center gap-2'}>
                        <CreateNetworkModal />
                    </div>
                </div>
            )}
            <EditNetworkInterfaceModal />
            <DeleteNetworkInterfaceModal />
            <VlanFormModal mode={'create'} />
            <VlanFormModal mode={'edit'} />
            <DeleteVlanModal />
            {isError && !interfaces ? (
                <Card className={'py-6'}>
                    <CollectionErrorState onRetry={refetch} />
                </Card>
            ) : isLoading ? (
                <div className={'flex flex-col gap-2 @md:gap-4'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24'} />
                    ))}
                </div>
            ) : interfaces?.length === 0 ? (
                <Card className={'py-6'}>
                    <SimpleEmptyState
                        icon={IconWifiOff}
                        title={'No network interfaces'}
                        description={
                            'Add a network interface to connect servers on this node.'
                        }
                        action={<CreateNetworkModal />}
                    />
                </Card>
            ) : (
                // Framed rather than a stack of cards: a node usually has one
                // or two interfaces, and one card per interface makes a page of
                // mostly padding. The frame owns the rows, so the list reads as
                // one object whether it holds one bridge or fifteen.
                <Card className={'divide-y py-0'}>
                    {interfaces?.map(networkInterface => (
                        <NetworkInterfaceCard
                            key={networkInterface.id}
                            interface={networkInterface}
                        />
                    ))}
                </Card>
            )}
        </>
    )
}
