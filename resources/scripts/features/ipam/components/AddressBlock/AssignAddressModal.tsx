import {
    addressQueries,
    updateAddress,
} from '@/features/ipam/blocks/addresses/api.ts'
import { addressBlockQueries } from '@/features/ipam/blocks/api.ts'
import ServerPicker from '@/features/ipam/components/AddressBlock/ServerPicker.tsx'
import { AddressBlock } from '@/types/address-block.ts'
import { PaginatedAddresses } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconLink } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { queryClient } from '@/lib/query-client.ts'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { SelectForm } from '@/components/ui/Forms'
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

/**
 * How many free addresses the picker offers. The default answer is the first one; the list exists
 * so an operator who wants a particular address is not sent back to the table to find it.
 */
const CHOICES = 50

const schema = z.object({
    addressId: z.string().min(1, 'Pick an address to assign.'),
    serverId: z.string().min(1, 'Pick a server to assign it to.'),
})

interface Props {
    block: AddressBlock | undefined
    mutate: Mutator<PaginatedAddresses>
}

/**
 * Assignment, the way round an operator actually performs it.
 *
 * Attaching a server used to be an *edit* on a row you first had to find: open the block, page to
 * the address, open its menu, pick a server, save. The question is almost never "what shall I do
 * with .86" — it is "this server needs an address", and the block already knows which one is next.
 * So the dialog opens on the next free address and asks only for the server.
 */
const AssignAddressModal = ({ block, mutate }: Props) => {
    const [open, setOpen] = useState(false)

    const groupId = block?.addressBlockGroupId
    const blockId = block?.id

    // Fetched only while the dialog is open: it is a snapshot of what is free right now, and
    // holding it warm behind a closed dialog would just go stale.
    const { data: free, isLoading } = useQuery({
        ...addressQueries.list(
            groupId!,
            blockId!,
            {
                page: 1,
                perPage: CHOICES,
                filters: { state: 'available' },
            },
            []
        ),
        enabled: open && !!groupId && !!blockId,
    })

    const choices = free?.items ?? []
    const next = choices[0]

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: { addressId: '', serverId: '' },
    })

    // The default is the next free address, but only until the operator picks another: re-applying
    // it on every refetch would drag their choice back to the top of the list.
    useEffect(() => {
        if (!next || form.getValues('addressId')) return

        form.setValue('addressId', String(next.id))
    }, [next, form])

    const submit = async (values: z.infer<typeof schema>) => {
        if (!groupId || !blockId) return

        try {
            await updateAddress(
                groupId,
                blockId,
                Number(values.addressId),
                Number(values.serverId)
            )

            await mutate()
            // The block's counts and its map both moved.
            await Promise.all([
                queryClient.invalidateQueries({
                    queryKey: addressBlockQueries.detail(groupId, blockId)
                        .queryKey,
                }),
                queryClient.invalidateQueries({
                    queryKey: addressQueries.all(groupId, blockId),
                }),
            ])

            form.reset({ addressId: '', serverId: '' })
            setOpen(false)
            toast.add({ title: 'Address assigned', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to assign the address', type: 'error' })

            throw e
        }
    }

    if (!block) {
        return null
    }

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={next => {
                setOpen(next)

                if (!next) form.reset({ addressId: '', serverId: '' })
            }}
        >
            <ResponsiveDialogTrigger
                render={
                    <Button icon={<IconLink className={'size-4'} />}>
                        Assign next free
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Assign an address
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Hand an address from this block to a server.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'flex flex-col gap-4'}>
                            {isLoading ? (
                                <p className={'text-muted-foreground text-sm'}>
                                    Looking for a free address…
                                </p>
                            ) : next ? (
                                <Alert>
                                    <AlertDescription>
                                        Next free in this block is{' '}
                                        <span className={'font-mono'}>
                                            {next.ip}
                                        </span>
                                        .
                                    </AlertDescription>
                                </Alert>
                            ) : (
                                <Alert variant={'destructive'}>
                                    <AlertDescription>
                                        This block has no free addresses. Free
                                        one, or generate more if the block is
                                        not fully generated.
                                    </AlertDescription>
                                </Alert>
                            )}

                            {next && (
                                <>
                                    <ServerPicker
                                        addressBlockGroupId={
                                            block.addressBlockGroupId
                                        }
                                    />
                                    <SelectForm
                                        name={'addressId'}
                                        label={'Address'}
                                        description={
                                            'Defaults to the next free address in the block.'
                                        }
                                        items={choices.map(address => ({
                                            value: String(address.id),
                                            label: address.ip,
                                        }))}
                                    />
                                </>
                            )}
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'sm:mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton disabled={!next}>Assign</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AssignAddressModal
