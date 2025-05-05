import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'
import useNetworkInterfacesSWR from '@/api/admin/nodes/networkInterfaces/use-network-interfaces-swr.ts'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { IconDatabase, IconWifi, IconWifiOff } from '@tabler/icons-react'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/network')({
    component: NodeNetwork,
})

function NodeNetwork() {
    const { data: interfaces, isLoading } = useNetworkInterfacesSWR()

    return (
        <>
            <Heading>Network</Heading>
            {isLoading ? <div className={'flex flex-col gap-2 @md:gap-4'}>
                {Array.from({ length: 4 }).map((_, index) => (
                    <Skeleton key={index} className={'h-24'} />
                ))}
            </div> : interfaces?.length === 0 ? <Card>
                <CardHeader className={'pb-0'} />
                <CardContent>
                    <SimpleEmptyState
                        icon={IconWifiOff}
                        title={'Network Interfaces'}
                        description={
                            'No network interfaces have been created for this node yet.'
                        }
                    />
                </CardContent>
            </Card> :  <div className={'flex flex-col gap-2 @md:gap-4'}>

            </div>}

        </>
    )
}
