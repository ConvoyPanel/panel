import { IconWifiOff } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'

import CreateNetworkModal from '@/features/nodes/components/Network/CreateNetworkModal.tsx'
import NetworkInterfaceCard from '@/features/nodes/components/Network/NetworkInterfaceCard.tsx'

import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'
import EditNetworkInterfaceModal from '@/features/nodes/components/Network/EditNetworkInterfaceModal.tsx'
import DeleteNetworkInterfaceModal from '@/features/nodes/components/Network/DeleteNetworkInterfaceModal.tsx'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/network')({
    component: NodeNetwork,
})

function NodeNetwork() {
    const { data: interfaces, isLoading } = useNetworkInterfaces()

    return (
        <>
            <div className={'flex flex-wrap items-center justify-between gap-2'}>
                <Heading>Network</Heading>
                {(isLoading || Boolean(interfaces?.length)) && (
                    <CreateNetworkModal />
                )}
            </div>
            <EditNetworkInterfaceModal />
            <DeleteNetworkInterfaceModal />
            {isLoading ? (
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
                    {interfaces!.map(networkInterface => (
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
