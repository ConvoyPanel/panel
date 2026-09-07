import { useIpamSummary } from '@/features/ipam/api.ts'
import { addressCapacity } from '@/features/ipam/capacity.ts'
import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import {
    IconAlertTriangle,
    IconCircleDashed,
    IconNetwork,
} from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { ReactNode } from 'react'

import { SegmentedProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton.tsx'

const Value = ({ children }: { children: ReactNode }) => (
    <span
        className={
            'text-lg font-semibold tracking-tight @sm:text-xl @xl:text-2xl'
        }
    >
        {children}
    </span>
)

const Sub = ({ children }: { children: ReactNode }) => (
    <span className={'text-muted-foreground block text-sm'}>{children}</span>
)

const countOf = (count: number, noun: string) =>
    `${count.toLocaleString()} ${noun}${count === 1 ? '' : 's'}`

/**
 * What the whole install's address space looks like, above the list of pools.
 *
 * Three tiles rather than four: a "nodes served" tile would repeat a column the table already
 * carries, and a row of near-empty cards is the thing this screen was short of information to
 * justify. "Blocks over 90%" earns its place because it names a block, which the table can only
 * show once you have scrolled to the right pool.
 */
const IpamSummaryCards = () => {
    const { data: summary } = useIpamSummary()
    const view = summary ? addressCapacity(summary.capacity) : null

    return (
        <div className={'grid grid-cols-1 gap-4 @2xl:grid-cols-3'}>
            <StatisticCard
                compact
                title={'Addresses in use'}
                icon={IconNetwork}
                meter={
                    view?.known ? (
                        <SegmentedProgressBar
                            segments={view.segments}
                            aria-label={`${view.percent!.toFixed(0)}% of usable addresses are in use`}
                        />
                    ) : undefined
                }
            >
                {view ? (
                    <p>
                        <Value>{view.inUse.toLocaleString()}</Value>
                        <Sub>
                            {view.usable === null
                                ? 'assigned or held'
                                : `of ${view.usable.toLocaleString()} usable${
                                      view.percent === null
                                          ? ''
                                          : ` · ${view.percent.toFixed(0)}%`
                                  }`}
                            {view.sparseBlockCount > 0 &&
                                ` · ${countOf(view.sparseBlockCount, 'block')} minted on demand`}
                        </Sub>
                    </p>
                ) : (
                    <Skeleton className={'h-7 w-24'} />
                )}
            </StatisticCard>

            <StatisticCard
                compact
                title={'Free addresses'}
                icon={IconCircleDashed}
            >
                {summary && view ? (
                    <p>
                        <Value>{view.free.toLocaleString()}</Value>
                        <Sub>
                            {view.free > 0
                                ? `ready to hand out across ${countOf(summary.poolsCount, 'pool')}`
                                : view.ungenerated > 0
                                  ? `${view.ungenerated.toLocaleString()} units not generated yet`
                                  : 'nothing left to hand out'}
                        </Sub>
                    </p>
                ) : (
                    <Skeleton className={'h-7 w-24'} />
                )}
            </StatisticCard>

            <StatisticCard
                compact
                title={'Blocks over 90%'}
                icon={IconAlertTriangle}
            >
                {summary ? (
                    <p>
                        <Value>
                            <span
                                className={
                                    summary.blocksNearlyFull > 0
                                        ? 'text-address-reserved'
                                        : undefined
                                }
                            >
                                {summary.blocksNearlyFull}
                            </span>
                        </Value>
                        <Sub>
                            {summary.fullestBlock ? (
                                <Link
                                    className={
                                        'hover:text-foreground font-mono'
                                    }
                                    to={
                                        '/admin/ipam/$addressBlockGroupId/blocks/$addressBlockId'
                                    }
                                    params={{
                                        addressBlockGroupId: String(
                                            summary.fullestBlock
                                                .addressBlockGroupId
                                        ),
                                        addressBlockId: String(
                                            summary.fullestBlock.id
                                        ),
                                    }}
                                >
                                    {summary.fullestBlock.label} ·{' '}
                                    {summary.fullestBlock.percent}%
                                </Link>
                            ) : (
                                `${countOf(summary.blocksCount, 'block')} with room to spare`
                            )}
                        </Sub>
                    </p>
                ) : (
                    <Skeleton className={'h-7 w-24'} />
                )}
            </StatisticCard>
        </div>
    )
}

export default IpamSummaryCards
