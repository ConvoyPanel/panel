import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import type { UserResources } from '@/types/admin/user.ts'
import {
    IconCpu,
    IconDatabase,
    IconDeviceDesktopAnalytics,
    IconServer,
    IconWifi,
} from '@tabler/icons-react'
import byteSize from 'byte-size'

import LinearProgressBar from '@/components/ui/Progress/LinearProgressBar.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    resources: UserResources | undefined
}

const format = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 1 })

    return `${size.value} ${size.unit}`
}

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

/** The figure, in the type the statistic row uses everywhere else. */
const Value = ({ children }: { children: React.ReactNode }) => (
    <span
        className={'text-lg font-semibold tracking-tight @sm:text-xl @xl:text-2xl'}
    >
        {children}
    </span>
)

const Sub = ({ children }: { children: React.ReactNode }) => (
    <span className={'text-muted-foreground block text-sm'}>{children}</span>
)

/**
 * What one account is holding, as the same tiles the server overview uses.
 *
 * Allocation, not consumption: these are the limits written on the account's servers, which is the
 * figure an operator sizing a node or reading a support ticket is after. Live CPU and memory usage
 * belongs to a server, and lives on that server's own page.
 */
const UserResourceCards = ({ resources }: Props) => {
    if (!resources) {
        return (
            <div
                className={
                    'grid grid-cols-2 gap-4 @2xl:grid-cols-3 @5xl:grid-cols-5'
                }
            >
                {Array.from({ length: 5 }).map((_, index) => (
                    <Skeleton key={index} className={'h-[7.5rem] w-full'} />
                ))}
            </div>
        )
    }

    const isUnmetered = resources.bandwidthLimit === null
    const usedPercent =
        resources.bandwidthLimit && resources.bandwidthLimit > 0
            ? Math.min(
                  (resources.bandwidthUsage / resources.bandwidthLimit) * 100,
                  100
              )
            : 0

    /*
     * Suspension and an unfinished build are separate facts about a server and a server can be
     * both, so they are listed rather than summed into one "not healthy" number.
     */
    const serverNotes = [
        resources.suspendedCount > 0 && `${resources.suspendedCount} suspended`,
        resources.unbuiltCount > 0 && `${resources.unbuiltCount} not built`,
    ].filter(Boolean) as string[]

    return (
        <div
            className={'grid grid-cols-2 gap-4 @2xl:grid-cols-3 @5xl:grid-cols-5'}
        >
            <StatisticCard compact title={'Servers'} icon={IconServer}>
                <p>
                    <Value>{resources.serversCount}</Value>
                    <Sub>
                        {serverNotes.length > 0
                            ? serverNotes.join(' · ')
                            : resources.serversCount > 0
                              ? 'all active'
                              : 'none yet'}
                    </Sub>
                </p>
            </StatisticCard>

            <StatisticCard compact title={'vCPU'} icon={IconCpu}>
                <p>
                    <Value>{resources.cpu}</Value>
                    <Sub>
                        {resources.nodesCount > 0
                            ? `allocated across ${countOf(resources.nodesCount, 'node')}`
                            : 'allocated'}
                    </Sub>
                </p>
            </StatisticCard>

            <StatisticCard compact title={'Memory'} icon={IconDeviceDesktopAnalytics}>
                <p>
                    <Value>{format(resources.memory)}</Value>
                    <Sub>allocated</Sub>
                </p>
            </StatisticCard>

            <StatisticCard compact title={'Disk'} icon={IconDatabase}>
                <p>
                    <Value>{format(resources.disk)}</Value>
                    <Sub>allocated</Sub>
                </p>
            </StatisticCard>

            <StatisticCard
                compact
                title={'Bandwidth'}
                icon={IconWifi}
                className={'col-span-2 @2xl:col-span-1'}
                meter={
                    /* No ratio, no bar — an unmetered account is not a full one. */
                    !isUnmetered && (
                        <LinearProgressBar
                            value={usedPercent}
                            aria-label={`${usedPercent.toFixed(0)}% of the summed bandwidth allowance is used`}
                        />
                    )
                }
            >
                <p>
                    <Value>{format(resources.bandwidthUsage)}</Value>
                    <Sub>
                        {isUnmetered
                            ? 'used · at least one server unmetered'
                            : `of ${format(resources.bandwidthLimit ?? 0)} this period`}
                    </Sub>
                </p>
            </StatisticCard>
        </div>
    )
}

export default UserResourceCards
