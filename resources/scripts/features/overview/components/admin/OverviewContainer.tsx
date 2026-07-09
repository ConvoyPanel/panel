import { overviewQueries } from '@/features/overview/api'
import { cn } from '@/utils'
import { useQuery } from '@tanstack/react-query'
import { ReactNode } from 'react'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Spinner from '@/components/ui/Spinner.tsx'
import { Heading } from '@/components/ui/Typography'

import NeedsAttentionCard from './NeedsAttentionCard'
import NodesCard from './NodesCard'
import Sparkline from './Sparkline'
import {
    bytes,
    capacityTone,
    meterIndicatorClass,
    num,
    toneBadgeClass,
} from './overview-helpers'

type OverviewData = App.Data.Admin.Overview.OverviewData
type Breakdown = App.Data.Admin.Overview.ServerStatusBreakdownData
type MetricTrend = App.Data.Admin.Overview.MetricTrendData

/** Top-line count with a muted label, a week-over-week delta, and a trend sparkline. */
const MetricTile = ({
    label,
    value,
    trend,
}: {
    label: string
    value: ReactNode
    trend: MetricTrend
}) => {
    const delta = trend.delta
    const rounded = delta === null ? 0 : Math.round(delta)
    const showDelta = delta !== null && rounded !== 0
    const up = rounded > 0

    return (
        <div className='bg-card overflow-hidden rounded-xl border p-4'>
            <div className='flex items-start justify-between gap-2'>
                <div className='text-muted-foreground text-sm'>{label}</div>
                {showDelta && (
                    <span
                        className={cn(
                            'text-xs font-semibold tabular-nums',
                            up
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-muted-foreground'
                        )}
                        title='Change vs. last week'
                    >
                        {up ? '▲' : '▼'} {Math.abs(rounded)}
                    </span>
                )}
            </div>
            <div className='mt-1 text-2xl font-semibold tracking-tight tabular-nums'>
                {value}
            </div>
            {trend.series.length >= 2 && (
                <Sparkline
                    series={trend.series}
                    className='mt-3 -mb-4 -mx-4 h-8 w-[calc(100%+2rem)]'
                />
            )}
        </div>
    )
}

/** One capacity resource: label + % badge + figure + tone meter (+ optional sub). */
const CapacityMeter = ({
    label,
    figure,
    unit,
    percent,
    hasData,
    emptyText,
    sub,
}: {
    label: string
    figure?: string
    unit?: string
    percent: number
    hasData: boolean
    emptyText: string
    sub?: string
}) => {
    const tone = capacityTone(percent)
    return (
        <div>
            <div className='flex items-center justify-between'>
                <span className='text-muted-foreground text-xs'>{label}</span>
                {hasData && (
                    <span
                        className={cn(
                            'rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums',
                            toneBadgeClass[tone]
                        )}
                    >
                        {percent}%
                    </span>
                )}
            </div>
            <div className='mt-0.5 mb-2 text-lg font-semibold tabular-nums'>
                {hasData ? (
                    <>
                        {figure}{' '}
                        <span className='text-muted-foreground text-xs font-normal'>
                            {unit}
                        </span>
                    </>
                ) : (
                    <span className='text-muted-foreground text-base font-normal'>
                        {emptyText}
                    </span>
                )}
            </div>
            <LinearProgressBar
                value={Math.min(percent, 100)}
                indicatorClassName={meterIndicatorClass[tone]}
            />
            {sub && (
                <div className='text-muted-foreground mt-1.5 text-xs'>
                    {sub}
                </div>
            )}
        </div>
    )
}

const STATUS_META: ReadonlyArray<
    [keyof Omit<Breakdown, 'total' | 'statuses'>, string, string]
> = [
    ['ready', 'Ready', 'bg-emerald-500'],
    ['installing', 'Installing', 'bg-amber-500'],
    ['restoring', 'Restoring', 'bg-amber-500'],
    ['suspended', 'Suspended', 'bg-muted-foreground/60'],
    ['deleting', 'Deleting', 'bg-muted-foreground/60'],
    ['failed', 'Failed', 'bg-destructive'],
]

const StatusRow = ({
    label,
    count,
    barClass,
}: {
    label: string
    count: number
    barClass: string
}) => {
    const zero = count === 0
    return (
        <div className='flex items-center gap-3 border-b py-2 first:pt-0 last:border-0 last:pb-0'>
            <span
                className={cn(
                    'h-4 w-[3px] shrink-0 rounded-full',
                    zero ? 'bg-border' : barClass
                )}
            />
            <span className={cn('text-sm', zero && 'text-muted-foreground/60')}>
                {label}
            </span>
            <span
                className={cn(
                    'ml-auto text-[15px] font-semibold tabular-nums',
                    zero && 'text-muted-foreground/60 font-normal'
                )}
            >
                {num(count)}
            </span>
        </div>
    )
}

/** A labelled figure used in the Backups & ISOs grid. */
const Stat = ({
    label,
    value,
    danger,
}: {
    label: string
    value: ReactNode
    danger?: boolean
}) => (
    <div>
        <div className='text-muted-foreground text-xs'>{label}</div>
        <div
            className={cn(
                'mt-0.5 text-xl font-semibold tabular-nums',
                danger && 'text-destructive'
            )}
        >
            {value}
        </div>
    </div>
)

const Dashboard = ({ data }: { data: OverviewData }) => {
    const {
        summary,
        servers,
        memory,
        storage,
        addresses,
        backups,
        isos,
        nodes,
    } = data

    const isFresh = summary.servers === 0
    const successRate =
        backups.total > 0
            ? Math.round((backups.successful / backups.total) * 100)
            : 0

    return (
        <div className='@container min-w-0 space-y-4'>
            <Heading>Admin Dashboard</Heading>

            {/* Top-line counts */}
            <div className='grid grid-cols-2 gap-4 @xl:grid-cols-4'>
                <MetricTile
                    label='Servers'
                    value={num(summary.servers)}
                    trend={data.trends.servers}
                />
                <MetricTile
                    label='Nodes'
                    value={num(summary.nodes)}
                    trend={data.trends.nodes}
                />
                <MetricTile
                    label='Users'
                    value={num(summary.users)}
                    trend={data.trends.users}
                />
                <MetricTile
                    label='Backups'
                    value={num(backups.total)}
                    trend={data.trends.backups}
                />
            </div>

            {/* Capacity + attention */}
            <div className='grid grid-cols-1 items-stretch gap-4 @2xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]'>
                <Card>
                    <CardHeader className='p-5 pb-2'>
                        <CardTitle className='text-base'>
                            Fleet capacity
                        </CardTitle>
                    </CardHeader>
                    <CardContent className='flex flex-col gap-5 p-5 pt-2'>
                        <CapacityMeter
                            label='Memory allocated'
                            figure={bytes(memory.allocated)}
                            unit={`of ${bytes(memory.total)}`}
                            percent={memory.percent}
                            hasData
                            emptyText=''
                        />
                        <CapacityMeter
                            label='Storage allocated'
                            figure={bytes(storage.allocated)}
                            unit={`of ${bytes(storage.total)}`}
                            percent={storage.percent}
                            hasData={storage.total > 0}
                            emptyText='No storage pools yet'
                        />
                        <CapacityMeter
                            label='IP addresses'
                            figure={`${num(addresses.assigned)} / ${num(addresses.total)}`}
                            unit='assigned'
                            percent={addresses.percent}
                            hasData={addresses.total > 0}
                            emptyText='No IP pools yet'
                            sub={
                                addresses.total > 0
                                    ? `${num(addresses.pools)} pools · ${num(addresses.available)} available`
                                    : undefined
                            }
                        />
                    </CardContent>
                </Card>

                <NeedsAttentionCard data={data} isFresh={isFresh} />
            </div>

            {/* Status + backups/isos */}
            <div className='grid grid-cols-1 gap-4 @xl:grid-cols-2'>
                <Card>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 p-5 pb-2'>
                        <CardTitle className='text-base'>
                            Servers by status
                        </CardTitle>
                        <span className='text-muted-foreground text-xs tabular-nums'>
                            {num(servers.total)} total
                        </span>
                    </CardHeader>
                    <CardContent className='p-5 pt-2'>
                        {STATUS_META.map(([key, label, barClass]) => (
                            <StatusRow
                                key={key}
                                label={label}
                                count={servers[key]}
                                barClass={barClass}
                            />
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className='p-5 pb-2'>
                        <CardTitle className='text-base'>
                            Backups &amp; ISOs
                        </CardTitle>
                    </CardHeader>
                    <CardContent className='p-5 pt-2'>
                        {backups.total > 0 || isos.total > 0 ? (
                            <>
                                <div className='grid grid-cols-3 gap-4'>
                                    <Stat
                                        label='Backups'
                                        value={num(backups.total)}
                                    />
                                    <Stat
                                        label='Success rate'
                                        value={`${successRate}%`}
                                    />
                                    <Stat
                                        label='Failed'
                                        value={num(backups.failed)}
                                        danger={backups.failed > 0}
                                    />
                                </div>
                                <LinearProgressBar
                                    value={successRate}
                                    className='mt-4'
                                />
                                <div className='my-4 border-t' />
                                <div className='text-muted-foreground mb-3 text-xs font-semibold'>
                                    ISOs
                                </div>
                                <div className='grid grid-cols-3 gap-4'>
                                    <Stat
                                        label='Ready'
                                        value={num(isos.successful)}
                                    />
                                    <Stat
                                        label='Pending'
                                        value={num(isos.pending)}
                                    />
                                    <Stat
                                        label='Total'
                                        value={num(isos.total)}
                                    />
                                </div>
                            </>
                        ) : (
                            <p className='text-muted-foreground py-2 text-sm'>
                                Nothing backed up yet — backups appear once
                                servers run.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <NodesCard nodes={nodes} />
        </div>
    )
}

const OverviewContainer = () => {
    const { data, isPending, isError } = useQuery(overviewQueries.metrics())

    if (isPending) {
        return (
            <div className='flex justify-center py-16'>
                <Spinner className='h-6 w-6' />
            </div>
        )
    }

    if (isError) {
        return (
            <p className='text-muted-foreground py-16 text-center text-sm'>
                Couldn't load the dashboard metrics. Try again shortly.
            </p>
        )
    }

    return <Dashboard data={data} />
}

export default OverviewContainer
