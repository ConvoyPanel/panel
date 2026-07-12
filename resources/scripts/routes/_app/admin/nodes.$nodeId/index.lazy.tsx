import { createLazyFileRoute } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { useNode } from '@/features/nodes/api.ts'
import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { useStorages } from '@/features/nodes/storages/api.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemTitle,
} from '@/components/ui/Item'
import { LinearProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading, Stat, StatLabel } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/')({
    component: NodeOverview,
})

const formatBytes = (value: number) => {
    const formatted = byteSize(value, { units: 'iec', precision: 2 })

    return `${formatted.value} ${formatted.unit}`
}

const percentage = (value: number, total: number) =>
    total > 0 ? Math.round((value / total) * 100) : 0

function NodeOverview() {
    const { nodeId } = Route.useParams()
    const numericNodeId = Number(nodeId)
    const { data: node, isLoading: nodeIsLoading } = useNode(numericNodeId)
    const { data: storages, isLoading: storagesAreLoading } =
        useStorages(numericNodeId)
    const { data: interfaces, isLoading: interfacesAreLoading } =
        useNetworkInterfaces(numericNodeId)

    if (nodeIsLoading || !node) {
        return (
            <>
                <Heading>Overview</Heading>
                <div
                    className={
                        '@container grid grid-cols-1 gap-4 @xl:grid-cols-2'
                    }
                >
                    <Skeleton className={'h-40'} />
                    <Skeleton className={'h-40'} />
                    <Skeleton className={'h-52 @xl:col-span-2'} />
                </div>
            </>
        )
    }

    const memoryPercent = percentage(node.memoryAllocated, node.memory)
    const freeMemory = Math.max(node.memory - node.memoryAllocated, 0)
    const storageCount = storages?.length ?? 0
    const networkInterfaceCount = interfaces?.length ?? 0
    const vlanAwareCount =
        interfaces?.filter(item => item.isVlanAware).length ?? 0
    const backupStorageCount =
        storages?.filter(storage => storage.storesBackups).length ?? 0
    const storageNoun = storageCount === 1 ? 'pool' : 'pools'
    const interfaceNoun =
        networkInterfaceCount === 1 ? 'interface' : 'interfaces'
    const storageSummary = storagesAreLoading
        ? 'Loading storage configuration'
        : `${storageCount} storage ${storageNoun} configured, ${backupStorageCount} backup-capable`
    const networkSummary = interfacesAreLoading
        ? 'Loading network configuration'
        : `${networkInterfaceCount} ${interfaceNoun} configured, ${vlanAwareCount} VLAN-aware`

    return (
        <div className={'@container space-y-4'}>
            <Heading>Overview</Heading>

            <div className={'grid grid-cols-1 gap-4 @xl:grid-cols-2'}>
                <Card>
                    <CardHeader>
                        <CardTitle>Node Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className={'grid grid-cols-2 gap-4'}>
                            <div>
                                <dt className={'text-xs text-muted-foreground'}>
                                    Display name
                                </dt>
                                <dd className={'text-sm'}>
                                    {node.displayName}
                                </dd>
                            </div>
                            <div>
                                <dt className={'text-xs text-muted-foreground'}>
                                    Proxmox node
                                </dt>
                                <dd className={'text-sm'}>{node.name}</dd>
                            </div>
                            <div>
                                <dt className={'text-xs text-muted-foreground'}>
                                    FQDN
                                </dt>
                                <dd className={'truncate text-sm font-mono'}>
                                    {node.fqdn}
                                </dd>
                            </div>
                            <div>
                                <dt className={'text-xs text-muted-foreground'}>
                                    API port
                                </dt>
                                <dd className={'text-sm tabular-nums'}>
                                    {node.port}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Capacity</CardTitle>
                    </CardHeader>
                    <CardContent className={'space-y-4'}>
                        <div>
                            <div
                                className={
                                    'mb-2 flex items-center justify-between gap-3'
                                }
                            >
                                <StatLabel className={'text-xs'}>
                                    Memory allocated
                                </StatLabel>
                                <span
                                    className={
                                        'text-sm font-semibold tabular-nums'
                                    }
                                >
                                    {memoryPercent}%
                                </span>
                            </div>
                            <LinearProgressBar
                                value={Math.min(memoryPercent, 100)}
                            />
                        </div>
                        <dl className={'grid grid-cols-3 gap-4'}>
                            <Stat
                                label={'Allocated'}
                                value={formatBytes(node.memoryAllocated)}
                            />
                            <Stat
                                label={'Available'}
                                value={formatBytes(freeMemory)}
                            />
                            <Stat
                                label={'Total'}
                                value={formatBytes(node.memory)}
                            />
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Configured Resources</CardTitle>
                </CardHeader>
                <CardContent>
                    <ItemGroup className={'gap-3'}>
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent>
                                <ItemTitle>Servers</ItemTitle>
                                <ItemDescription>
                                    {node.serversCount} server
                                    {node.serversCount === 1 ? '' : 's'} assigned
                                    to this node
                                </ItemDescription>
                            </ItemContent>
                        </Item>
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent>
                                <ItemTitle>Storages</ItemTitle>
                                <ItemDescription>
                                    {storageSummary}
                                </ItemDescription>
                            </ItemContent>
                        </Item>
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent>
                                <ItemTitle>Network Interfaces</ItemTitle>
                                <ItemDescription>
                                    {networkSummary}
                                </ItemDescription>
                            </ItemContent>
                        </Item>
                    </ItemGroup>
                </CardContent>
            </Card>
        </div>
    )
}
