import { generateAddresses } from '@/features/ipam/blocks/addresses/api.ts'
import { addressBlockQueries } from '@/features/ipam/blocks/api.ts'
import { addressCapacity } from '@/features/ipam/capacity.ts'
import { AddressBlock } from '@/types/address-block.ts'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    block: AddressBlock | undefined
    mutate: Mutator<PaginatedAddresses>
}

/**
 * Generating a block's addresses, with what actually happens on screen.
 *
 * The old control was a bare "Generate IPs" button that fired on click and toasted
 * `createdCount`. `GeneratedAddressesData` also reports `remaining`, `isComplete` and `sparse`,
 * and dropping those three made the action lie in both directions: a large block writes one batch
 * per click with nothing saying thousands are left, and a sparse block — which is minted on
 * demand and has nothing to generate — reported "Generated 0 addresses", which reads as failure.
 */
const GenerateAddressesModal = ({ block, mutate }: Props) => {
    const [open, setOpen] = useState(false)
    const view = block ? addressCapacity(block.capacity) : null

    const { mutate: trigger, isPending } = useMutation({
        mutationFn: () =>
            generateAddresses(block!.addressBlockGroupId, block!.id),
        onSuccess: async result => {
            await mutate()
            // The block's own counts moved, and they are what the header meter draws.
            await queryClient.invalidateQueries({
                queryKey: addressBlockQueries.detail(
                    block!.addressBlockGroupId,
                    block!.id
                ).queryKey,
            })

            setOpen(false)

            if (result.sparse) {
                toast.add({
                    title: 'Nothing to generate',
                    description:
                        'This block is minted on demand — addresses are created as they are allocated.',
                    type: 'info',
                })

                return
            }

            toast.add({
                title: `Generated ${result.createdCount.toLocaleString()} addresses`,
                description: result.isComplete
                    ? 'Every address in this block now exists.'
                    : `${result.remaining.toLocaleString()} left — run it again to continue.`,
                type: 'success',
            })
        },
        onError: () => {
            toast.add({ title: 'Failed to generate addresses', type: 'error' })
        },
    })

    if (!block || !view) {
        return null
    }

    const cidr = `${block.baseIp}/${block.prefixLengthFrom}`
    const nothingToDo = view.isSparse || view.ungenerated < 1

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button
                        variant={'outline'}
                        icon={<IconPlus className={'size-4'} />}
                        disabled={nothingToDo}
                    >
                        Generate addresses
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Generate addresses
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Create the address rows this block can hand out.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody className={'flex flex-col gap-4'}>
                    <p className={'text-sm'}>
                        <span className={'font-mono'}>{cidr}</span> handed out
                        as{' '}
                        <span className={'font-mono'}>
                            /{block.prefixLengthTo}
                        </span>{' '}
                        covers{' '}
                        <b>{(view.total ?? 0).toLocaleString()} addresses</b>.{' '}
                        {view.total === view.ungenerated
                            ? 'None exist yet.'
                            : `${(view.total! - view.ungenerated).toLocaleString()} exist so far.`}
                    </p>
                    <Alert>
                        <AlertDescription>
                            Addresses are written in batches, so a block this
                            size may need more than one run. Each run reports
                            how many are left.
                        </AlertDescription>
                    </Alert>
                    <dl className={'grid grid-cols-3 gap-3'}>
                        <div className={'flex flex-col-reverse'}>
                            <dt className={'text-muted-foreground text-xs'}>
                                Covers
                            </dt>
                            <dd className={'font-mono text-sm'}>
                                {(view.total ?? 0).toLocaleString()}
                            </dd>
                        </div>
                        <div className={'flex flex-col-reverse'}>
                            <dt className={'text-muted-foreground text-xs'}>
                                To create
                            </dt>
                            <dd className={'font-mono text-sm'}>
                                {view.ungenerated.toLocaleString()}
                            </dd>
                        </div>
                        <div className={'flex flex-col-reverse'}>
                            <dt className={'text-muted-foreground text-xs'}>
                                Gateway
                            </dt>
                            <dd className={'font-mono text-sm'}>
                                {block.gateway ?? '—'}
                            </dd>
                        </div>
                    </dl>
                    <p className={'text-muted-foreground text-xs'}>
                        The network, broadcast and gateway addresses are created
                        reserved — they exist so nothing else can take them, and
                        they cannot be released.
                    </p>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter className={'sm:mt-4'}>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'} type={'button'}>
                                Cancel
                            </Button>
                        }
                    />
                    <Button onClick={() => trigger()} loading={isPending}>
                        Generate
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default GenerateAddressesModal
