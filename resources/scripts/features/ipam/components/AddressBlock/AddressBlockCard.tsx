import { addressCapacity } from '@/features/ipam/capacity.ts'
import GenerateAddressesModal from '@/features/ipam/components/AddressBlock/GenerateAddressesModal.tsx'
import AddressCapacityMeter from '@/features/ipam/components/AddressCapacityMeter.tsx'
import AddressStateLabel from '@/features/ipam/components/AddressStateLabel.tsx'
import { useAddressBlockModal } from '@/features/ipam/hooks/use-address-block-modal.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { AddressBlock } from '@/types/address-block.ts'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'

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

interface Props {
    block: AddressBlock | undefined
    mutate: Mutator<PaginatedAddresses>
}

/**
 * What the block is and how full it is, above the addresses themselves.
 *
 * The page used to open with the block's name (or its CIDR when it had none) and nothing else, so
 * the gateway, the output prefix and the number of addresses left were all things you worked out
 * by paging through the table.
 */
const AddressBlockCard = ({ block, mutate }: Props) => {
    const openModal = useOpenModal(useAddressBlockModal)
    const view = block ? addressCapacity(block.capacity) : null

    const description = () => {
        if (!block || !view) return ' '

        const geometry =
            view.total === null
                ? `Hands out /${block.prefixLengthTo} on demand`
                : `Hands out /${block.prefixLengthTo} · ${view.total.toLocaleString()} units`

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
                    <CardAction>
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
                {block && view ? (
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
                                    {view.usable === null
                                        ? '—'
                                        : view.usable.toLocaleString()}
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
                                        {view.free.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                        </dl>
                    </>
                ) : (
                    <div className={'flex flex-col gap-3'}>
                        <Skeleton className={'h-2 w-full rounded-full'} />
                        <Skeleton className={'h-10 w-full'} />
                    </div>
                )}
            </CardContent>
            {/* The primary action belongs in the divided footer, not loose beside the heading. */}
            <CardFooter className={'justify-between gap-3'}>
                {/* Generation status, not a repeat of the counts two lines above — and the reason
                    the button beside it is disabled when it is. */}
                <span className={'text-muted-foreground text-sm tabular-nums'}>
                    {!view
                        ? ''
                        : view.isSparse
                          ? 'Addresses are minted as they are allocated'
                          : view.ungenerated > 0
                            ? `${view.ungenerated.toLocaleString()} of ${(view.total ?? 0).toLocaleString()} not generated yet`
                            : `All ${(view.total ?? 0).toLocaleString()} addresses generated`}
                </span>
                <GenerateAddressesModal block={block} mutate={mutate} />
            </CardFooter>
        </Card>
    )
}

export default AddressBlockCard
