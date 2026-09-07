import {
    AddressBulkAction,
    bulkAddresses,
} from '@/features/ipam/blocks/addresses/api.ts'
import { addressBlockQueries } from '@/features/ipam/blocks/api.ts'
import {
    Address,
    AddressState,
    AddressStateReason,
    PaginatedAddresses,
} from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { useMutation } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import { Button } from '@/components/ui/Button'
import { toast } from '@/components/ui/Toast'

interface Props {
    addresses: Address[]
    addressBlockGroupId: number
    addressBlockId: number
    mutate: Mutator<PaginatedAddresses>
}

const VERBS: Record<AddressBulkAction, { done: string; none: string }> = {
    reserve: { done: 'reserved', none: 'None of those could be reserved' },
    release: { done: 'released', none: 'None of those could be released' },
    delete: { done: 'deleted', none: 'None of those could be deleted' },
}

/**
 * The buttons inside the table's selection bar.
 *
 * Reserving a run of infrastructure addresses used to be one trip through a row menu per address —
 * and one audit entry per address. Which buttons appear follows the selection: there is no point
 * offering Release when nothing selected is held.
 */
const AddressBulkActions = ({
    addresses,
    addressBlockGroupId,
    addressBlockId,
    mutate,
}: Props) => {
    const { mutate: run, isPending } = useMutation({
        mutationFn: ({ action }: { action: AddressBulkAction }) =>
            bulkAddresses(
                addressBlockGroupId,
                addressBlockId,
                action,
                addresses.map(address => address.id)
            ),
        onSuccess: async result => {
            await mutate()
            // The block's counts moved, and they are what the header meter draws.
            await queryClient.invalidateQueries({
                queryKey: addressBlockQueries.detail(
                    addressBlockGroupId,
                    addressBlockId
                ).queryKey,
            })

            const verb = VERBS[result.action]

            toast.add({
                title:
                    result.affected > 0
                        ? `${result.affected.toLocaleString()} ${result.affected === 1 ? 'address' : 'addresses'} ${verb.done}`
                        : verb.none,
                // Skipped rows are the normal case, not an error: a selection dragged over a table
                // will contain system reservations and assigned addresses, and saying nothing about
                // them leaves the operator counting rows to work out what happened.
                description:
                    result.skipped > 0
                        ? `${result.skipped.toLocaleString()} skipped — assigned or reserved by the panel.`
                        : undefined,
                type: result.affected > 0 ? 'success' : 'info',
            })
        },
        onError: () =>
            toast.add({ title: 'Bulk action failed', type: 'error' }),
    })

    const canReserve = addresses.some(
        address => address.state === AddressState.Available
    )
    const canRelease = addresses.some(
        address =>
            address.state === AddressState.Reserved &&
            address.stateReason !== AddressStateReason.System
    )
    const canDelete = addresses.some(
        address => address.state !== AddressState.Assigned
    )

    return (
        <>
            {canReserve && (
                <Button
                    size={'sm'}
                    variant={'outline'}
                    loading={isPending}
                    onClick={() => run({ action: 'reserve' })}
                >
                    Reserve
                </Button>
            )}
            {canRelease && (
                <Button
                    size={'sm'}
                    variant={'outline'}
                    loading={isPending}
                    onClick={() => run({ action: 'release' })}
                >
                    Release
                </Button>
            )}
            {canDelete && (
                <Button
                    size={'sm'}
                    variant={'destructive'}
                    loading={isPending}
                    onClick={() => run({ action: 'delete' })}
                >
                    Delete
                </Button>
            )}
        </>
    )
}

export default AddressBulkActions
