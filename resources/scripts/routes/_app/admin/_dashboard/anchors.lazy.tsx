import {
    anchorQueries,
    createAnchor,
    createEnrollment,
    deleteAnchor,
    updateAnchor,
    useAnchors,
} from '@/features/anchors/api'
import { Anchor, anchorSchema } from '@/features/anchors/types'
import useClipboard from '@/hooks/use-clipboard.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import type { z } from 'zod'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, SelectForm } from '@/components/ui/Forms'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/anchors')({
    component: AnchorsPage,
})

const compatibilityVariant = (value: Anchor['compatibility']) =>
    value === 'compatible'
        ? 'default'
        : value === 'incompatible'
          ? 'destructive'
          : 'secondary'

function AnchorsPage() {
    const confirm = useConfirmationStore(state => state.confirm)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useAnchors(queryParams)
    const [editing, setEditing] = useState<Anchor | 'new' | null>(null)
    const [enrollment, setEnrollment] = useState<string | null>(null)
    const { copy } = useClipboard({
        successMessage: 'Enrollment command copied',
    })

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: anchorQueries.all() })
    const enrollmentMutation = useMutation({
        mutationFn: (anchor: Anchor) => createEnrollment(anchor.id),
        onSuccess: result => setEnrollment(result.command),
        onError: () =>
            toast.add({
                title: 'Failed to create enrollment command',
                type: 'error',
            }),
    })
    const deleteMutation = useMutation({
        mutationFn: (anchor: Anchor) => deleteAnchor(anchor.id),
        onSuccess: async () => {
            toast.add({ title: 'Anchor deleted', type: 'success' })
            await refresh()
        },
        onError: () =>
            toast.add({ title: 'Failed to delete Anchor', type: 'error' }),
    })
    const handleDelete = async (anchor: Anchor) => {
        const confirmed = await confirm({
            title: 'Delete Anchor',
            description: `Delete "${anchor.name}"? Its enrollment and installation secret will be permanently removed.`,
        })
        if (confirmed) deleteMutation.mutate(anchor)
    }
    const actions = (anchor: Anchor) => (
        <>
            <DropdownMenuItem onClick={() => setEditing(anchor)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => enrollmentMutation.mutate(anchor)}>
                Enrollment command
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant='destructive'
                disabled={anchor.nodesCount > 0 || anchor.agentsCount > 0}
                onClick={() => handleDelete(anchor)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )
    const columns: ColumnDef<Anchor>[] = [
        { header: 'Name', accessorKey: 'name' },
        {
            header: 'Role',
            accessorKey: 'mode',
            cell: ({ cell }) => (
                <span className='capitalize'>{cell.getValue<string>()}</span>
            ),
        },
        { header: 'Endpoint', accessorKey: 'publicUrl' },
        {
            header: 'Compatibility',
            accessorKey: 'compatibility',
            cell: ({ cell }) => (
                <Badge
                    variant={compatibilityVariant(
                        cell.getValue<Anchor['compatibility']>()
                    )}
                    className='capitalize'
                >
                    {cell.getValue<string>()}
                </Badge>
            ),
        },
        {
            header: 'Version',
            accessorKey: 'version',
            cell: ({ cell }) =>
                cell.getValue<string | null>() ?? 'Not enrolled',
        },
        actionsColumn<Anchor>(({ row }) => actions(row.original)),
    ]
    return (
        <>
            <Heading>Anchors</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                searchable
                toolbar
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const anchor = row.original
                    return (
                        <Item variant='muted' size='sm'>
                            <ItemContent className='min-w-0'>
                                <ItemTitle className='flex items-center gap-2'>
                                    <span className='truncate'>
                                        {anchor.name}
                                    </span>
                                    <Badge
                                        variant='secondary'
                                        className='capitalize'
                                    >
                                        {anchor.mode}
                                    </Badge>
                                </ItemTitle>
                                <ItemDescription className='truncate'>
                                    {anchor.publicUrl}
                                </ItemDescription>
                                <div className='flex flex-wrap gap-2'>
                                    <Badge
                                        variant={compatibilityVariant(
                                            anchor.compatibility
                                        )}
                                        className='capitalize'
                                    >
                                        {anchor.compatibility}
                                    </Badge>
                                    <Badge variant='secondary'>
                                        {anchor.version ?? 'Not enrolled'}
                                    </Badge>
                                </div>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{actions(anchor)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <Button onClick={() => setEditing('new')}>
                        <IconPlus className='size-4' />
                        Add Anchor
                    </Button>
                }
                {...tableProps}
            />
            <AnchorForm
                anchor={editing}
                anchors={data?.items ?? []}
                close={() => setEditing(null)}
                refresh={refresh}
            />
            <ResponsiveDialog
                open={enrollment !== null}
                onOpenChange={open => !open && setEnrollment(null)}
            >
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            Enrollment command
                        </ResponsiveDialogTitle>
                    </ResponsiveDialogHeader>
                    <ResponsiveDialogBody>
                        <pre className='bg-muted overflow-x-auto rounded-md p-3 text-xs'>
                            {enrollment}
                        </pre>
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter>
                        <ResponsiveDialogClose
                            render={<Button variant='outline'>Close</Button>}
                        />
                        <Button onClick={() => enrollment && copy(enrollment)}>
                            Copy
                        </Button>
                    </ResponsiveDialogFooter>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

function AnchorForm({
    anchor,
    anchors,
    close,
    refresh,
}: {
    anchor: Anchor | 'new' | null
    anchors: Anchor[]
    close: () => void
    refresh: () => Promise<unknown>
}) {
    const current = anchor === 'new' ? null : anchor
    const form = useForm<z.infer<typeof anchorSchema>>({
        resolver: zodResolver(anchorSchema),
        values: {
            name: current?.name ?? '',
            mode: current?.mode ?? 'agent',
            publicUrl: current?.publicUrl ?? '',
            panelUrlOverride: current?.panelUrlOverride ?? '',
            relayId: current?.relayId?.toString() ?? 'none',
        },
    })
    const save = useMutation({
        mutationFn: (data: z.infer<typeof anchorSchema>) =>
            current ? updateAnchor(current.id, data) : createAnchor(data),
    })
    const submit = async (data: z.infer<typeof anchorSchema>) => {
        try {
            await save.mutateAsync(data)
            await refresh()
            toast.add({
                title: `Anchor ${current ? 'updated' : 'created'}`,
                type: 'success',
            })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to save Anchor', type: 'error' })
        }
    }
    return (
        <ResponsiveDialog
            open={anchor !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {current ? `Edit ${current.name}` : 'New Anchor'}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className='space-y-3'>
                            <InputForm name='name' label='Name' />
                            <SelectForm
                                name='mode'
                                label='Role'
                                items={[
                                    { value: 'agent', label: 'Agent' },
                                    { value: 'relay', label: 'Relay' },
                                ]}
                            />
                            <InputForm
                                name='publicUrl'
                                label='Connection URL'
                                placeholder='https://anchor.example.com'
                                description='Where the panel and your browser reach this Anchor.'
                            />
                            <InputForm
                                name='panelUrlOverride'
                                label='Panel URL override'
                                placeholder='Optional'
                                description='Where this Anchor reaches the panel, when the panel URL does not resolve on its network. Leave blank to use the default.'
                            />
                            <SelectForm
                                name='relayId'
                                label='Relay'
                                items={[
                                    {
                                        value: 'none',
                                        label: 'Direct connection',
                                    },
                                    ...anchors
                                        .filter(
                                            item =>
                                                item.mode === 'relay' &&
                                                item.id !== current?.id
                                        )
                                        .map(item => ({
                                            value: String(item.id),
                                            label: item.name,
                                        })),
                                ]}
                                disabled={form.watch('mode') === 'relay'}
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className='mt-4'>
                            <ResponsiveDialogClose
                                render={
                                    <Button type='button' variant='outline'>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}
