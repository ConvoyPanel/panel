import { anchorQueries, deleteAnchor, useAnchors } from '@/features/anchors/api'
import AnchorFormDialog from '@/features/anchors/components/AnchorFormDialog.tsx'
import AnchorList from '@/features/anchors/components/AnchorList.tsx'
import EnrollmentPanel from '@/features/anchors/components/EnrollmentPanel.tsx'
import anchorStatus, { type AnchorTone } from '@/features/anchors/status.ts'
import { Anchor } from '@/features/anchors/types'
import { IconFilter, IconPlus } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import { Input } from '@/components/ui/Input'
import {
    ResponsiveDialog,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

type RoleFilter = 'all' | 'agent' | 'relay'

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

const tones: { value: AnchorTone; label: string }[] = [
    { value: 'online', label: 'Online' },
    { value: 'waiting', label: 'Waiting for install' },
    { value: 'down', label: 'Unreachable or mismatched' },
]

/**
 * A fleet of anchors is a handful of long-lived machines, not a paginated
 * collection: they are added once, they live for years, and nobody sorts them.
 * So this is a roster rather than a DataTable -- no pager, and the search and
 * filter controls only appear once there are enough anchors for them to earn
 * the space.
 */
const AnchorsPage = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const [search, setSearch] = useState('')
    const [role, setRole] = useState<RoleFilter>('all')
    const [statuses, setStatuses] = useState<AnchorTone[]>([])
    const [editing, setEditing] = useState<Anchor | 'new' | null>(null)
    const [installing, setInstalling] = useState<Anchor | null>(null)

    const { data, isPending, isError, refetch } = useAnchors({
        perPage: 100,
        filters: { name: search, mode: role === 'all' ? null : role },
    })

    const items = data?.items ?? []
    // Role and name are real query filters; status is derived from timestamps
    // the server never indexes, so it narrows the loaded page instead.
    const anchors =
        statuses.length === 0
            ? items
            : items.filter(anchor =>
                  statuses.includes(anchorStatus(anchor).tone)
              )
    const total = data?.pagination.total ?? 0
    const filtered = search !== '' || role !== 'all' || statuses.length > 0
    const showToolbar = total > 8 || filtered

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: anchorQueries.all() })

    const deleteMutation = useMutation({
        mutationFn: (anchor: Anchor) => deleteAnchor(anchor.id),
        onSuccess: async () => {
            toast.add({ title: 'Anchor deleted', type: 'success' })
            await refresh()
        },
        onError: () =>
            toast.add({ title: 'Failed to delete anchor', type: 'error' }),
    })

    /*
     * Delete is always offered, and the confirmation is where it is refused.
     * Dropping the menu item for an anchor that still carries something leaves
     * the user to work out why the action they came for is missing; asking and
     * then answering "because these four nodes go dark with it" is the same
     * refusal with the reason attached.
     */
    const handleDelete = async (anchor: Anchor) => {
        const carried = [
            anchor.nodesCount > 0 ? countOf(anchor.nodesCount, 'node') : null,
            anchor.agentsCount > 0
                ? countOf(anchor.agentsCount, 'agent')
                : null,
        ].filter(Boolean)
        const blocked = carried.length > 0
        const one = anchor.nodesCount + anchor.agentsCount === 1

        const confirmed = await confirm({
            title: blocked ? `${anchor.name} is still in use` : 'Delete anchor',
            description: blocked
                ? `It carries ${carried.join(' and ')}, which ${one ? 'loses' : 'lose'} console access the moment it goes away. Move ${one ? 'it' : 'them'} to another anchor first.`
                : `Delete "${anchor.name}"? Its enrollment and installation secret will be permanently removed.`,
            confirmText: 'Delete',
            cancelText: blocked ? 'Close' : 'Cancel',
            confirmButton: { variant: 'destructive', disabled: blocked },
        })

        if (confirmed) deleteMutation.mutate(anchor)
    }

    // Same shape as every other admin index's primary action (see
    // nodes.index.lazy.tsx): plus icon, default size, right of the toolbar row.
    const addButton = (
        <Button onClick={() => setEditing('new')}>
            <IconPlus className='size-4' />
            Add anchor
        </Button>
    )

    const live = installing
        ? (items.find(item => item.id === installing.id) ?? installing)
        : null

    return (
        <>
            <Heading>Anchors</Heading>
            {/* The page's own spacing comes from AppLayout's flex column, and
                the toolbar-then-card rhythm from the same `space-y-4` DataTable
                uses -- this screen only opts out of the table, not the layout
                every other admin index has. */}
            <div className='@container space-y-4'>
                <div className='flex flex-wrap items-center gap-2'>
                    <div className='flex min-w-0 flex-1 flex-wrap items-center gap-2'>
                        {showToolbar && (
                            <>
                                <Input
                                    placeholder='Search...'
                                    value={search}
                                    onChange={event =>
                                        setSearch(event.target.value)
                                    }
                                    className='bg-background h-8 w-[150px] lg:w-[250px]'
                                    aria-label='Search anchors'
                                />
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant='outline'>
                                            <IconFilter className='size-4' />
                                            Filters
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align='start'
                                        className='w-56'
                                    >
                                        <DropdownMenuLabel>
                                            Role
                                        </DropdownMenuLabel>
                                        {(
                                            ['all', 'agent', 'relay'] as const
                                        ).map(value => (
                                            <DropdownMenuCheckboxItem
                                                key={value}
                                                checked={role === value}
                                                onCheckedChange={() =>
                                                    setRole(value)
                                                }
                                                className='capitalize'
                                            >
                                                {value === 'all'
                                                    ? 'Any'
                                                    : value}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                        <DropdownMenuSeparator />
                                        <DropdownMenuLabel>
                                            Status
                                        </DropdownMenuLabel>
                                        {tones.map(tone => (
                                            <DropdownMenuCheckboxItem
                                                key={tone.value}
                                                checked={statuses.includes(
                                                    tone.value
                                                )}
                                                onCheckedChange={checked =>
                                                    setStatuses(current =>
                                                        checked
                                                            ? [
                                                                  ...current,
                                                                  tone.value,
                                                              ]
                                                            : current.filter(
                                                                  item =>
                                                                      item !==
                                                                      tone.value
                                                              )
                                                    )
                                                }
                                            >
                                                {tone.label}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </>
                        )}
                    </div>
                    <div className='ml-auto flex items-center gap-2'>
                        {addButton}
                    </div>
                </div>

                {isError ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : (
                    <AnchorList
                        anchors={anchors}
                        isLoading={isPending}
                        onEdit={setEditing}
                        onInstall={setInstalling}
                        onDelete={handleDelete}
                        emptyAction={filtered ? undefined : addButton}
                    />
                )}
            </div>

            <AnchorFormDialog
                anchor={editing}
                anchors={items}
                close={() => setEditing(null)}
                refresh={refresh}
            />

            <ResponsiveDialog
                open={installing !== null}
                onOpenChange={open => !open && setInstalling(null)}
            >
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            Install on {live?.name}
                        </ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            Run this on the machine.
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    {live && (
                        <EnrollmentPanel
                            anchor={live}
                            refresh={refresh}
                            onClose={() => setInstalling(null)}
                        />
                    )}
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/anchors/')({
    component: AnchorsPage,
})
