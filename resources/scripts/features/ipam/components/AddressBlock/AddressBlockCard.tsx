import { addressCapacity } from '@/features/ipam/capacity.ts'
import AddressMap from '@/features/ipam/components/AddressBlock/AddressMap.tsx'
import AssignAddressModal from '@/features/ipam/components/AddressBlock/AssignAddressModal.tsx'
import GenerateAddressesModal from '@/features/ipam/components/AddressBlock/GenerateAddressesModal.tsx'
import AddressCapacityMeter from '@/features/ipam/components/AddressCapacityMeter.tsx'
import AddressStateLabel from '@/features/ipam/components/AddressStateLabel.tsx'
import { useAddressBlockModal } from '@/features/ipam/hooks/use-address-block-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { AddressBlock } from '@/types/address-block.ts'
import { AddressMap as AddressMapPayload } from '@/types/address-map.ts'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/ToggleGroup'

export type AddressBlockView = 'map' | 'list'

interface Props {
    block: AddressBlock | undefined
    mutate: Mutator<PaginatedAddresses>
    view: AddressBlockView
    onViewChange: (view: AddressBlockView) => void
    map: AddressMapPayload | undefined
    selectedIds: number[]
    onSelectedIdsChange: (ids: number[]) => void
    /** Shared with the list's faceted filter — one filter, two renderings. */
    stateFilter: string[]
    onStateFilterChange: (states: string[]) => void
    /** Rendered under the map while it has a selection — the same actions the table's bar offers. */
    bulkActions: ReactNode
}

/**
 * What the block is and how full it is, above the addresses themselves.
 *
 * The page used to open with the block's name (or its CIDR when it had none) and nothing else, so
 * the gateway, the output prefix and the number of addresses left were all things you worked out
 * by paging through the table.
 */
const AddressBlockCard = ({
    block,
    mutate,
    view,
    onViewChange,
    map,
    selectedIds,
    onSelectedIdsChange,
    stateFilter,
    onStateFilterChange,
    bulkActions,
}: Props) => {
    const openModal = useOpenModal(useAddressBlockModal)
    const capacity = block ? addressCapacity(block.capacity) : null

    const description = () => {
        if (!block || !capacity) return ' '

        const geometry =
            capacity.total === null
                ? `Hands out /${block.prefixLengthTo} on demand`
                : `Hands out /${block.prefixLengthTo} · ${capacity.total.toLocaleString()} units`

        return [
            block.name || block.description,
            geometry,
            block.gateway ? `gateway ${block.gateway}` : null,
        ]
            .filter(Boolean)
            .join(' · ')
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Address space</CardTitle>
                <CardDescription>{description()}</CardDescription>
                {block && (
                    <CardAction className={'flex items-center gap-2'}>
                        {/* Only offered when there is a map to show: a sparse or oversized block
                            has no grid, and a switch that leads to an explanation is worse than
                            no switch. */}
                        {map && !map.sparse && !map.tooLarge && (
                            <ToggleGroup
                                variant={'outline'}
                                size={'sm'}
                                spacing={0}
                                multiple={false}
                                value={[view]}
                                onValueChange={value => {
                                    // Single-select: ignore the empty array a second click on the
                                    // pressed item produces, so one segment is always active.
                                    if (value[0])
                                        onViewChange(
                                            value[0] as AddressBlockView
                                        )
                                }}
                                aria-label={'Show addresses as a map or a list'}
                            >
                                <ToggleGroupItem value={'map'}>
                                    Map
                                </ToggleGroupItem>
                                <ToggleGroupItem value={'list'}>
                                    List
                                </ToggleGroupItem>
                            </ToggleGroup>
                        )}
                        <Button
                            variant={'outline'}
                            onClick={() => openModal('edit', block)}
                        >
                            Edit block
                        </Button>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent className={'flex flex-col gap-4'}>
                {block && capacity ? (
                    <>
                        <AddressCapacityMeter
                            capacity={block.capacity}
                            size={'md'}
                            subline={false}
                        />
                        <dl
                            className={
                                'grid grid-cols-2 gap-3 @xl:grid-cols-5 @xl:gap-4'
                            }
                        >
                            {/* col-reverse: the figure reads first, but a `dl` requires its `dt`
                                to precede the `dd` it labels. */}
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Usable
                                </dt>
                                <dd className={'font-mono text-sm'}>
                                    {capacity.usable === null
                                        ? '—'
                                        : capacity.usable.toLocaleString()}
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Assigned
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'assigned'}>
                                        {block.capacity.assignedCount.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Reserved
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'reserved'}>
                                        {block.capacity.reservedCount.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    System
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'system'}>
                                        {block.capacity.systemCount.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Free
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'available'}>
                                        {capacity.free.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                        </dl>
                        {view === 'map' &&
                            map &&
                            !map.sparse &&
                            !map.tooLarge && (
                                <div className={'border-t pt-4'}>
                                    <AddressMap
                                        map={map}
                                        selectedIds={selectedIds}
                                        onSelectedIdsChange={
                                            onSelectedIdsChange
                                        }
                                        stateFilter={stateFilter}
                                        onStateFilterChange={
                                            onStateFilterChange
                                        }
                                    />
                                </div>
                            )}
                    </>
                ) : (
                    <div className={'flex flex-col gap-3'}>
                        <Skeleton className={'h-2 w-full rounded-full'} />
                        <Skeleton className={'h-10 w-full'} />
                    </div>
                )}
            </CardContent>
            {/* The primary action belongs in the divided footer, not loose beside the heading.
                A map selection borrows the same footer rather than introducing a second bar
                alongside the table's floating one. */}
            <CardFooter className={'justify-between gap-3'}>
                {/* Generation status, not a repeat of the counts two lines above — and the reason
                    the button beside it is disabled when it is. */}
                <span className={'text-muted-foreground text-sm tabular-nums'}>
                    {!capacity
                        ? ''
                        : capacity.isSparse
                          ? 'Addresses are minted as they are allocated'
                          : capacity.ungenerated > 0
                            ? `${capacity.ungenerated.toLocaleString()} of ${(capacity.total ?? 0).toLocaleString()} not generated yet`
                            : `All ${(capacity.total ?? 0).toLocaleString()} addresses generated`}
                </span>
                {selectedIds.length > 0 && view === 'map' ? (
                    <span className={'flex items-center gap-2'}>
                        <span className={'text-sm font-medium tabular-nums'}>
                            {selectedIds.length.toLocaleString()} selected
                        </span>
                        {bulkActions}
                        <Button
                            variant={'ghost'}
                            size={'sm'}
                            onClick={() => onSelectedIdsChange([])}
                        >
                            Clear
                        </Button>
                    </span>
                ) : (
                    <span className={'flex items-center gap-2'}>
                        <GenerateAddressesModal block={block} mutate={mutate} />
                        <AssignAddressModal block={block} mutate={mutate} />
                    </span>
                )}
            </CardFooter>
        </Card>
    )
}

export default AddressBlockCard
