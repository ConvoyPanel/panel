import { addressCapacity } from '@/features/ipam/capacity.ts'
import AddressCapacityMeter from '@/features/ipam/components/AddressCapacityMeter.tsx'
import AddressStateLabel from '@/features/ipam/components/AddressStateLabel.tsx'
import useBlockGroupModalStore from '@/features/ipam/hooks/use-block-group-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { AddressBlockGroup } from '@/types/address-block-group.ts'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    group: AddressBlockGroup | undefined
}

/**
 * What the pool is, on the page that is about it.
 *
 * The route used to render the pool's name as a bare `Heading` over two tabs, so its description,
 * its block and node counts and how full it is appeared nowhere — you had to open a block to find
 * out anything about the pool holding it.
 */
const AddressPoolCard = ({ group }: Props) => {
    const openModal = useOpenModal(useBlockGroupModalStore)
    const view = group ? addressCapacity(group.capacity) : null

    return (
        <Card>
            <CardHeader>
                <CardTitle>Address space</CardTitle>
                <CardDescription>
                    {group
                        ? group.description ||
                          'Address blocks routed to this pool’s nodes.'
                        : ' '}
                </CardDescription>
                {group && (
                    <CardAction>
                        <Button
                            variant={'outline'}
                            onClick={() => openModal('edit', group)}
                        >
                            Edit
                        </Button>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent className={'flex flex-col gap-4'}>
                {group && view ? (
                    <>
                        <AddressCapacityMeter
                            capacity={group.capacity}
                            size={'md'}
                            subline={false}
                        />
                        <dl
                            className={
                                'grid grid-cols-3 gap-3 @xl:grid-cols-6 @xl:gap-4'
                            }
                        >
                            {/* col-reverse: the figure reads first, but a `dl` requires its `dt`
                                to precede the `dd` it labels. */}
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Blocks
                                </dt>
                                <dd className={'text-sm tabular-nums'}>
                                    {group.addressBlocksCount}
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Nodes
                                </dt>
                                <dd className={'text-sm tabular-nums'}>
                                    {group.nodesCount}
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Assigned
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'assigned'}>
                                        {group.capacity.assignedCount.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    Reserved
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'reserved'}>
                                        {group.capacity.reservedCount.toLocaleString()}
                                    </AddressStateLabel>
                                </dd>
                            </div>
                            <div className={'flex flex-col-reverse'}>
                                <dt className={'text-muted-foreground text-xs'}>
                                    System
                                </dt>
                                <dd className={'text-sm'}>
                                    <AddressStateLabel kind={'system'}>
                                        {group.capacity.systemCount.toLocaleString()}
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
        </Card>
    )
}

export default AddressPoolCard
