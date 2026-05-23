import { bytesToString } from '@/util/helpers'
import {
    CircleStackIcon,
    ClockIcon,
    CpuChipIcon,
    ExclamationTriangleIcon,
    ServerStackIcon,
    SignalIcon,
    UsersIcon,
} from '@heroicons/react/24/outline'
import { Badge, Skeleton } from '@mantine/core'
import { ComponentType } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import useOverviewSWR from '@/api/admin/overview/useOverviewSWR'
import {
    DashboardActivity,
    DashboardMetric,
    DashboardNode,
} from '@/api/admin/overview/getOverview'

import Card from '@/components/elements/Card'
import MessageBox from '@/components/elements/MessageBox'
import PageContentBlock from '@/components/elements/PageContentBlock'

interface IconProps {
    className?: string
}

interface StatCardProps {
    title: string
    value: number | string
    detail: string
    icon: ComponentType<IconProps>
    to?: string
    tone?: 'default' | 'warning' | 'error'
}

const toneClasses = {
    default: 'text-accent-600 border-accent-200 bg-accent-100',
    warning: 'text-warning-dark border-warning bg-warning-lighter',
    error: 'text-error border-error-light bg-error-lighter',
}

const StatCard = ({
    title,
    value,
    detail,
    icon: Icon,
    to,
    tone = 'default',
}: StatCardProps) => {
    const content = (
        <div className='flex items-start justify-between gap-4'>
            <div>
                <p className='dt'>{title}</p>
                <p className='mt-3 text-3xl font-semibold text-foreground'>
                    {value}
                </p>
                <p className='description-small mt-2'>{detail}</p>
            </div>
            <div className={`rounded-md border p-2 ${toneClasses[tone]}`}>
                <Icon className='h-5 w-5' />
            </div>
        </div>
    )

    if (to) {
        return (
            <Link
                to={to}
                className='col-span-12 block sm:col-span-6 xl:col-span-3'
            >
                <Card className='h-full transition-shadow hover:shadow-lg'>
                    {content}
                </Card>
            </Link>
        )
    }

    return (
        <Card className='col-span-12 sm:col-span-6 xl:col-span-3'>
            {content}
        </Card>
    )
}

const UsageBar = ({
    label,
    metric,
}: {
    label: string
    metric: DashboardMetric
}) => {
    const { t } = useTranslation('admin.overview')

    return (
        <div>
            <div className='flex items-center justify-between gap-3'>
                <p className='text-sm font-medium text-foreground'>{label}</p>
                <p className='text-sm text-accent-500'>
                    {bytesToString(metric.allocated)} /{' '}
                    {bytesToString(metric.total)}
                </p>
            </div>
            <div className='mt-2 h-2 overflow-hidden rounded bg-accent-200'>
                <div
                    className={`h-full rounded ${
                        metric.percent >= 90
                            ? 'bg-error'
                            : metric.percent >= 75
                            ? 'bg-warning'
                            : 'bg-success'
                    }`}
                    style={{ width: `${Math.min(metric.percent, 100)}%` }}
                />
            </div>
            <p className='description-small mt-1'>
                {t('allocated_percent', { percent: metric.percent })}
            </p>
        </div>
    )
}

const NodeRow = ({ node }: { node: DashboardNode }) => {
    const { t } = useTranslation('admin.overview')
    const { t: tStrings } = useTranslation('strings')

    return (
        <div className='border-b border-accent-200 py-4 last:border-0 last:pb-0'>
            <div className='flex flex-wrap items-start justify-between gap-3'>
                <div>
                    <Link
                        to={`/admin/nodes/${node.id}`}
                        className='link text-foreground'
                    >
                        {node.name}
                    </Link>
                    <p className='description-small mt-1'>
                        {node.cluster} - {node.fqdn}
                    </p>
                </div>
                <Badge variant='outline' color='gray' className='!normal-case'>
                    {t('servers_count', { count: node.servers })}
                </Badge>
            </div>
            <div className='mt-4 grid gap-4 lg:grid-cols-2'>
                <UsageBar label={tStrings('memory')} metric={node.memory} />
                <UsageBar label={tStrings('disk')} metric={node.disk} />
            </div>
        </div>
    )
}

const ActivityRow = ({ activity }: { activity: DashboardActivity }) => {
    const { t } = useTranslation('admin.overview')

    return (
        <div className='border-b border-accent-200 py-3 last:border-0 last:pb-0'>
            <div className='flex items-start justify-between gap-3'>
                <div className='min-w-0'>
                    <p className='truncate text-sm font-medium text-foreground'>
                        {activity.description || activity.event}
                    </p>
                    <p className='description-small mt-1'>
                        {activity.actor || t('system')} - {activity.event}
                    </p>
                </div>
                <p className='whitespace-nowrap text-xs text-accent-400'>
                    {new Date(activity.timestamp).toLocaleTimeString()}
                </p>
            </div>
        </div>
    )
}

const OverviewSkeleton = () => (
    <div className='grid grid-cols-12 gap-6'>
        {[1, 2, 3, 4].map(item => (
            <Skeleton
                key={item}
                className='col-span-12 sm:col-span-6 xl:col-span-3'
                height={142}
            />
        ))}
        <Skeleton className='col-span-12 lg:col-span-7' height={420} />
        <Skeleton className='col-span-12 lg:col-span-5' height={420} />
    </div>
)

const OverviewContainer = () => {
    const { t } = useTranslation('admin.overview')
    const { t: tStrings } = useTranslation('strings')
    const { data, error } = useOverviewSWR()

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title={tStrings('overview') ?? ''}>
                <div className='mb-6 flex flex-wrap items-center justify-between gap-3'>
                    <div>
                        <h1 className='text-2xl font-semibold text-foreground'>
                            {tStrings('overview')}
                        </h1>
                        <p className='description-small mt-1'>
                            {t('description')}
                        </p>
                    </div>
                </div>

                {error && (
                    <MessageBox
                        type='error'
                        title={tStrings('error') ?? ''}
                        className='mb-6'
                    >
                        {t('load_error')}
                    </MessageBox>
                )}

                {!data ? (
                    <OverviewSkeleton />
                ) : (
                    <div className='grid grid-cols-12 gap-6'>
                        <StatCard
                            title={tStrings('server', { count: 2 })}
                            value={data.summary.servers}
                            detail={t('ready_installing_detail', {
                                ready: data.servers.ready,
                                installing: data.servers.installing,
                            })}
                            icon={ServerStackIcon}
                            to='/admin/servers'
                        />
                        <StatCard
                            title={tStrings('node', { count: 2 })}
                            value={data.summary.nodes}
                            detail={t('memory_allocated_detail', {
                                memory: bytesToString(
                                    data.capacity.memory.allocated
                                ),
                            })}
                            icon={CpuChipIcon}
                            to='/admin/nodes'
                        />
                        <StatCard
                            title={tStrings('user', { count: 2 })}
                            value={data.summary.users}
                            detail={t('locations_configured_detail', {
                                count: data.summary.locations,
                            })}
                            icon={UsersIcon}
                            to='/admin/users'
                        />
                        <StatCard
                            title={t('attention')}
                            value={data.summary.failedServers}
                            detail={t('attention_detail', {
                                backups: data.backups.failed,
                                deleting: data.servers.deleting,
                            })}
                            icon={ExclamationTriangleIcon}
                            to='/admin/servers'
                            tone={
                                data.summary.failedServers > 0
                                    ? 'error'
                                    : 'default'
                            }
                        />

                        <Card className='col-span-12 lg:col-span-7'>
                            <div className='flex items-center justify-between gap-3'>
                                <div>
                                    <h2 className='h5'>{t('capacity')}</h2>
                                    <p className='description-small mt-1'>
                                        {t('capacity_description')}
                                    </p>
                                </div>
                                <CircleStackIcon className='h-5 w-5 text-accent-400' />
                            </div>
                            <div className='mt-6 grid gap-6'>
                                <UsageBar
                                    label={tStrings('memory')}
                                    metric={data.capacity.memory}
                                />
                                <UsageBar
                                    label={tStrings('disk')}
                                    metric={data.capacity.disk}
                                />
                            </div>

                            <div className='mt-8 grid gap-4 sm:grid-cols-3'>
                                <div className='border-l border-accent-200 py-1 pl-4'>
                                    <p className='dt'>{t('addresses')}</p>
                                    <p className='mt-2 text-xl font-semibold text-foreground'>
                                        {data.addresses.assigned} /{' '}
                                        {data.addresses.total}
                                    </p>
                                    <p className='description-small mt-1'>
                                        {t('addresses_detail', {
                                            available:
                                                data.addresses.available,
                                            pools: data.addresses.pools,
                                        })}
                                    </p>
                                </div>
                                <div className='border-l border-accent-200 py-1 pl-4'>
                                    <p className='dt'>
                                        {tStrings('backup', { count: 2 })}
                                    </p>
                                    <p className='mt-2 text-xl font-semibold text-foreground'>
                                        {data.backups.successful} /{' '}
                                        {data.backups.total}
                                    </p>
                                    <p className='description-small mt-1'>
                                        {t('backup_status_detail', {
                                            pending: data.backups.pending,
                                            failed: data.backups.failed,
                                        })}
                                    </p>
                                </div>
                                <div className='border-l border-accent-200 py-1 pl-4'>
                                    <p className='dt'>
                                        {tStrings('iso', { count: 2 })}
                                    </p>
                                    <p className='mt-2 text-xl font-semibold text-foreground'>
                                        {data.isos.successful} /{' '}
                                        {data.isos.total}
                                    </p>
                                    <p className='description-small mt-1'>
                                        {t('iso_status_detail', {
                                            pending: data.isos.pending,
                                        })}
                                    </p>
                                </div>
                            </div>
                        </Card>

                        <Card className='col-span-12 lg:col-span-5'>
                            <div className='flex items-center justify-between gap-3'>
                                <div>
                                    <h2 className='h5'>{t('server_state')}</h2>
                                    <p className='description-small mt-1'>
                                        {t('server_state_description')}
                                    </p>
                                </div>
                                <SignalIcon className='h-5 w-5 text-accent-400' />
                            </div>
                            <div className='mt-6 grid grid-cols-2 gap-3'>
                                {[
                                    [t('ready'), data.servers.ready],
                                    [t('installing'), data.servers.installing],
                                    [
                                        tStrings('suspended'),
                                        data.servers.suspended,
                                    ],
                                    [t('restoring'), data.servers.restoring],
                                    [t('deleting'), data.servers.deleting],
                                    [t('failed'), data.servers.failed],
                                ].map(([label, value]) => (
                                    <div
                                        key={label}
                                        className='border-l border-accent-200 py-1 pl-4'
                                    >
                                        <p className='dt'>{label}</p>
                                        <p className='mt-2 text-2xl font-semibold text-foreground'>
                                            {value}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        <Card className='col-span-12 lg:col-span-7'>
                            <h2 className='h5'>
                                {tStrings('node', { count: 2 })}
                            </h2>
                            <p className='description-small mt-1'>
                                {t('nodes_description')}
                            </p>
                            <div className='mt-2'>
                                {data.nodes.length === 0 ? (
                                    <p className='description-small py-6 text-center'>
                                        {t('no_nodes')}
                                    </p>
                                ) : (
                                    data.nodes.map(node => (
                                        <NodeRow key={node.id} node={node} />
                                    ))
                                )}
                            </div>
                        </Card>

                        <Card className='col-span-12 lg:col-span-5'>
                            <div className='flex items-center justify-between gap-3'>
                                <div>
                                    <h2 className='h5'>
                                        {t('recent_activity')}
                                    </h2>
                                    <p className='description-small mt-1'>
                                        {t('recent_activity_description')}
                                    </p>
                                </div>
                                <ClockIcon className='h-5 w-5 text-accent-400' />
                            </div>
                            <div className='mt-3'>
                                {data.activity.length === 0 ? (
                                    <p className='description-small py-6 text-center'>
                                        {t('no_activity')}
                                    </p>
                                ) : (
                                    data.activity.map(activity => (
                                        <ActivityRow
                                            key={activity.id}
                                            activity={activity}
                                        />
                                    ))
                                )}
                            </div>
                        </Card>
                    </div>
                )}
            </PageContentBlock>
        </div>
    )
}

export default OverviewContainer
