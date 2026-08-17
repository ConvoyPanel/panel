import { useNode, useNodeStatus } from '@/features/nodes/api.ts'
import NodeStatusIndicator from '@/features/nodes/components/NodeStatusIndicator.tsx'
import { connectionErrorCopy } from '@/features/nodes/connection-errors.ts'
import { getApiErrorCode } from '@/utils/http.ts'
import byteSize from 'byte-size'
import { useState } from 'react'

import { mapConnectionErrorType } from '@/lib/transformers/node.ts'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

interface NodeOverviewProps {
    nodeId: number
}

const formatBytes = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${size.value} ${size.unit}`
}

const formatDuration = (seconds: number) => {
    const days = Math.floor(seconds / 86_400)
    const hours = Math.floor((seconds % 86_400) / 3_600)
    const minutes = Math.floor((seconds % 3_600) / 60)

    return [days > 0 && `${days}d`, hours > 0 && `${hours}h`, `${minutes}m`]
        .filter(Boolean)
        .join(' ')
}

const usagePercent = (used: number, total: number) =>
    total > 0 ? Math.min((used / total) * 100, 100) : 0

const formatPveVersion = (version: string) => version.split('/')[1] ?? version

const NodeOverview = ({ nodeId }: NodeOverviewProps) => {
    const { data: node } = useNode(nodeId)

    /*
     * The poller already knows this node does not answer, and it wrote that to
     * the row within the last minute. Asking PVE anyway does not discover
     * anything new -- it just occupies a worker for the whole connect timeout,
     * on mount and again every 30s, on precisely the nodes that take longest to
     * fail. So the live call is suppressed while the stored status says
     * `unreachable`, and the cards render the cause the poller already
     * classified.
     *
     * Only `unreachable` suppresses it. `unknown` means nobody has asked yet,
     * and one call for one node is a reasonable way to find out.
     */
    const [checkRequested, setCheckRequested] = useState(false)
    const isKnownUnreachable = node?.status === 'unreachable'
    const shouldCheck = !isKnownUnreachable || checkRequested

    const {
        data: status,
        isError: isStatusError,
        error: statusError,
        isFetching: isStatusFetching,
        refetch: refetchStatus,
    } = useNodeStatus(nodeId, shouldCheck)
    const liveStatus = isStatusError ? undefined : status

    /*
     * One cause, from whichever source actually knows it. Suppressed: the
     * `status_code` the poller stored. Attempted and failed: the code the
     * endpoint classified via `NodeUnreachableException`. Both run through the
     * connection test's copy, so an untrusted certificate reads the same
     * sentence here as it did when the node was added.
     */
    const failure = !shouldCheck
        ? connectionErrorCopy(mapConnectionErrorType(node?.statusCode ?? ''))
        : isStatusError
          ? connectionErrorCopy(
                mapConnectionErrorType(getApiErrorCode(statusError) ?? '')
            )
          : null

    const allocationLimit = node
        ? node.memory * (1 + node.memoryOverallocate / 100)
        : 0
    const allocationPercent = node
        ? usagePercent(node.memoryAllocated, allocationLimit)
        : 0

    return (
        <>
            <div className='space-y-1'>
                {node ? (
                    <>
                        <div className='flex flex-wrap items-center gap-3'>
                            <Heading>{node.displayName}</Heading>
                            {/*
                             * The node's reachability lives here, in space the
                             * header already reserves, rather than in a banner
                             * that shoves the whole page down when it arrives.
                             *
                             * Read from the stored status via the same indicator
                             * the Nodes table uses, not from whether this page's
                             * own request happened to succeed. Inferring it here
                             * was a second status vocabulary: it had no way to
                             * say `unknown`, so a node reading "Unknown" on the
                             * list read "Unreachable" on its own detail page.
                             */}
                            <NodeStatusIndicator node={node} />
                        </div>
                        <p className='text-muted-foreground text-sm'>
                            {node.fqdn}:{node.port}
                        </p>
                    </>
                ) : (
                    <Skeleton className='h-8 w-48' />
                )}
            </div>

            <div className='grid grid-cols-1 gap-4 @md:grid-cols-2 @xl:grid-cols-3'>
                <Card>
                    <CardHeader>
                        <CardTitle>Live resources</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {liveStatus ? (
                            <div className='space-y-4'>
                                <div className='space-y-2'>
                                    <div className='flex justify-between gap-4 text-sm'>
                                        <span className='text-muted-foreground'>
                                            CPU
                                        </span>
                                        <span>
                                            {(
                                                liveStatus.cpuUsage * 100
                                            ).toFixed(1)}
                                            %
                                        </span>
                                    </div>
                                    <LinearProgressBar
                                        value={liveStatus.cpuUsage * 100}
                                        aria-label='CPU usage'
                                    />
                                </div>
                                <div className='space-y-2'>
                                    <div className='flex justify-between gap-4 text-sm'>
                                        <span className='text-muted-foreground'>
                                            Memory
                                        </span>
                                        <span className='text-right'>
                                            {formatBytes(
                                                liveStatus.memory.used
                                            )}{' '}
                                            /{' '}
                                            {formatBytes(
                                                liveStatus.memory.total
                                            )}
                                        </span>
                                    </div>
                                    <LinearProgressBar
                                        value={usagePercent(
                                            liveStatus.memory.used,
                                            liveStatus.memory.total
                                        )}
                                        aria-label='Memory usage'
                                    />
                                </div>
                                <dl className='grid grid-cols-2 gap-y-3 text-sm'>
                                    <dt className='text-muted-foreground'>
                                        Load average
                                    </dt>
                                    <dd className='text-right'>
                                        {liveStatus.loadAverage
                                            .map(value => value.toFixed(2))
                                            .join(' / ')}
                                    </dd>
                                    <dt className='text-muted-foreground'>
                                        Uptime
                                    </dt>
                                    <dd className='text-right'>
                                        {formatDuration(
                                            liveStatus.uptimeSeconds
                                        )}
                                    </dd>
                                </dl>
                            </div>
                        ) : failure ? (
                            /*
                             * The card that lost its data is the one that
                             * explains why, in the space it already occupies.
                             */
                            <div className='flex flex-col items-start gap-2'>
                                <p className='text-sm font-medium'>
                                    {failure.title}
                                </p>
                                <p className='text-muted-foreground text-sm'>
                                    {failure.description}
                                </p>
                                <Button
                                    variant='outline'
                                    size='sm'
                                    loading={isStatusFetching}
                                    onClick={() =>
                                        shouldCheck
                                            ? refetchStatus()
                                            : setCheckRequested(true)
                                    }
                                >
                                    {/*
                                     * Suppressed, this is the only thing that
                                     * asks the node -- so it says what it does.
                                     * "Retry" would imply something had already
                                     * been tried and failed here.
                                     */}
                                    {shouldCheck ? 'Retry' : 'Check anyway'}
                                </Button>
                            </div>
                        ) : (
                            <Skeleton className='h-36 w-full' />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Allocation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {node ? (
                            <div className='space-y-4'>
                                <div className='space-y-2'>
                                    <div className='flex justify-between gap-4 text-sm'>
                                        <span className='text-muted-foreground'>
                                            Memory committed
                                        </span>
                                        <span className='text-right'>
                                            {formatBytes(node.memoryAllocated)}{' '}
                                            / {formatBytes(allocationLimit)}
                                        </span>
                                    </div>
                                    <LinearProgressBar
                                        value={allocationPercent}
                                        aria-label='Committed memory allocation'
                                    />
                                </div>
                                <dl className='grid grid-cols-2 gap-y-3 text-sm'>
                                    <dt className='text-muted-foreground'>
                                        Physical memory
                                    </dt>
                                    <dd className='text-right'>
                                        {formatBytes(node.memory)}
                                    </dd>
                                    <dt className='text-muted-foreground'>
                                        Overallocation
                                    </dt>
                                    <dd className='text-right'>
                                        {node.memoryOverallocate}%
                                    </dd>
                                    <dt className='text-muted-foreground'>
                                        Servers
                                    </dt>
                                    <dd className='text-right'>
                                        {node.serversCount}
                                    </dd>
                                </dl>
                            </div>
                        ) : (
                            <Skeleton className='h-32 w-full' />
                        )}
                    </CardContent>
                </Card>

                <Card className='@md:col-span-2 @xl:col-span-1'>
                    <CardHeader>
                        <CardTitle>System</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {liveStatus && node ? (
                            <dl className='grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 text-sm'>
                                <dt className='text-muted-foreground'>CPU</dt>
                                <dd className='text-right'>
                                    {liveStatus.cpu.model}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Topology
                                </dt>
                                <dd className='text-right'>
                                    {liveStatus.cpu.socketCount} socket /{' '}
                                    {liveStatus.cpu.coreCount} cores /{' '}
                                    {liveStatus.cpu.cpuCount} threads
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Root filesystem
                                </dt>
                                <dd className='text-right'>
                                    {formatBytes(
                                        liveStatus.rootFilesystem.used
                                    )}{' '}
                                    /{' '}
                                    {formatBytes(
                                        liveStatus.rootFilesystem.total
                                    )}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Cluster
                                </dt>
                                <dd className='text-right'>
                                    {/*
                                     * Standalone is an answer, not a blank. It is
                                     * also what explains an empty "Attach
                                     * existing" list: a host in no cluster shares
                                     * no storage with anything.
                                     */}
                                    {node.clusterName ?? 'Standalone'}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Proxmox VE
                                </dt>
                                <dd className='text-right'>
                                    {formatPveVersion(liveStatus.pveVersion)}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    Kernel
                                </dt>
                                <dd className='text-right'>
                                    {liveStatus.kernel.release}
                                </dd>
                                <dt className='text-muted-foreground'>Boot</dt>
                                <dd className='text-right'>
                                    {liveStatus.boot.mode === 'efi'
                                        ? 'EFI'
                                        : 'Legacy BIOS'}
                                    {liveStatus.boot.secureBoot
                                        ? ' · Secure Boot'
                                        : ''}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    TLS verification
                                </dt>
                                <dd className='text-right'>
                                    {node.verifyTls ? 'Enabled' : 'Disabled'}
                                </dd>
                            </dl>
                        ) : !failure ? (
                            <Skeleton className='h-40 w-full' />
                        ) : node ? (
                            <dl className='grid grid-cols-2 gap-y-3 text-sm'>
                                <dt className='text-muted-foreground'>
                                    Proxmox name
                                </dt>
                                <dd className='text-right'>{node.name}</dd>
                                <dt className='text-muted-foreground'>
                                    Cluster
                                </dt>
                                <dd className='text-right'>
                                    {node.clusterName ?? 'Standalone'}
                                </dd>
                                <dt className='text-muted-foreground'>
                                    TLS verification
                                </dt>
                                <dd className='text-right'>
                                    {node.verifyTls ? 'Enabled' : 'Disabled'}
                                </dd>
                            </dl>
                        ) : (
                            <Skeleton className='h-24 w-full' />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    )
}

export default NodeOverview
