import CreateNetworkModal from '@/features/nodes/components/Network/CreateNetworkModal.tsx'
import DeleteNetworkInterfaceModal from '@/features/nodes/components/Network/DeleteNetworkInterfaceModal.tsx'
import EditNetworkInterfaceModal from '@/features/nodes/components/Network/EditNetworkInterfaceModal.tsx'
import NetworkInterfaceCard from '@/features/nodes/components/Network/NetworkInterfaceCard.tsx'
import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { IconWifiOff } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
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
            <div
                className={'flex flex-wrap items-center justify-between gap-2'}
            >
                <Heading>Network</Heading>
                {Boolean(interfaces?.length) && <CreateNetworkModal />}
            </div>
            <EditNetworkInterfaceModal />
            <DeleteNetworkInterfaceModal />
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
                <ItemGroup className={'gap-3'}>
                    {interfaces?.map(networkInterface => (
                        <NetworkInterfaceCard
                            key={networkInterface.id}
                            interface={networkInterface}
                        />
                    ))}
                </ItemGroup>
            )}
        </>
    )
}
