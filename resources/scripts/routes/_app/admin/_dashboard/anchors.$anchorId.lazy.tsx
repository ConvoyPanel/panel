import {
    anchorQueries,
    deleteAnchor,
    useAnchor,
    useAnchors,
} from '@/features/anchors/api'
import AnchorDependentsCard from '@/features/anchors/components/AnchorDependentsCard.tsx'
import AnchorFormDialog from '@/features/anchors/components/AnchorFormDialog.tsx'
import AnchorProtocol from '@/features/anchors/components/AnchorProtocol.tsx'
import AnchorStatusCell from '@/features/anchors/components/AnchorStatusCell.tsx'
import EnrollmentPanel from '@/features/anchors/components/EnrollmentPanel.tsx'
import type { Anchor } from '@/features/anchors/types'
import { cn } from '@/utils'
import { useMutation } from '@tanstack/react-query'
import { Link, createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { formatDistanceToNowStrict } from 'date-fns'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button, buttonVariants } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import {
    ResponsiveDialog,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const Detail = ({
    label,
    wrap,
    children,
}: {
    label: string
    /** For values that are lists rather than single opaque strings. */
    wrap?: boolean
    children: React.ReactNode
}) => (
    <div className='min-w-0'>
        <dt className='text-muted-foreground text-xs'>{label}</dt>
        <dd className={cn('mt-0.5', wrap ? 'break-words' : 'truncate')}>
            {children}
        </dd>
    </div>
)

const when = (value: string | null) =>
    value
        ? formatDistanceToNowStrict(new Date(value), { addSuffix: true })
        : '—'

const AnchorDetailPage = () => {
    const { anchorId } = Route.useParams()
    const navigate = useNavigate()
    const confirm = useConfirmationStore(state => state.confirm)
    const {
        data: anchor,
        isPending,
        isError,
        refetch,
    } = useAnchor(Number(anchorId))
    /*
     * The detail endpoint returns one anchor, but two things here need the rest
     * of the fleet: the edit form's "Route through" select, and a relay's list
     * of the agents pointed at it -- there is no relay_id filter on the index,
     * and at fleet sizes this page is built for, one call covers both.
     */
    const { data: fleet } = useAnchors({ perPage: 100 })
    const [editing, setEditing] = useState<Anchor | 'new' | null>(null)
    const [installing, setInstalling] = useState(false)

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: anchorQueries.all() })

    const deleteMutation = useMutation({
        mutationFn: (target: Anchor) => deleteAnchor(target.id),
        onSuccess: async () => {
            toast.add({ title: 'Anchor deleted', type: 'success' })
            await refresh()
            await navigate({ to: '/admin/anchors' })
        },
        onError: () =>
            toast.add({ title: 'Failed to delete anchor', type: 'error' }),
    })

    if (isError) return <CollectionErrorState onRetry={refetch} />

    if (isPending || !anchor) {
        return (
            <div className='flex flex-col gap-4'>
                <Skeleton className='h-8 w-64' />
                <Skeleton className='h-48 w-full' />
            </div>
        )
    }

    const handleDelete = async () => {
        const carried = [
            anchor.nodesCount > 0
                ? `${anchor.nodesCount} node${anchor.nodesCount === 1 ? '' : 's'}`
                : null,
            anchor.agentsCount > 0
                ? `${anchor.agentsCount} agent${anchor.agentsCount === 1 ? '' : 's'}`
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

    return (
        <>
            <div className='flex flex-wrap items-center justify-between gap-3'>
                <Heading>{anchor.name}</Heading>
                <div className='flex items-center gap-2'>
                    <Button
                        variant='outline'
                        onClick={() => setInstalling(true)}
                    >
                        {anchor.compatibility === 'unenrolled'
                            ? 'Install command'
                            : 'Reissue install command'}
                    </Button>
                    <Button onClick={() => setEditing(anchor)}>Edit</Button>
                </div>
            </div>

            {/*
             * A rail rather than a stack: the dependents list is the only thing
             * on this page whose length is somebody else's decision, so it gets
             * the wide column and everything fixed -- the facts, and the one
             * destructive action -- sits beside it where the list cannot push
             * it anywhere.
             *
             * `@3xl` measures AppLayout's content area, not this grid
             * (docs/card-design.md), so it was picked by measuring the rendered
             * page rather than by arithmetic: with the sidebar expanded the
             * rail holds three columns down to a 1100px viewport and stacks at
             * 1000px, with no horizontal overflow at any width down to 760px.
             */}
            <div className='grid gap-4 @3xl:grid-cols-3'>
                <div className='@3xl:col-span-2'>
                    <AnchorDependentsCard
                        anchor={anchor}
                        fleet={fleet?.items ?? []}
                    />
                </div>

                <div className='flex flex-col gap-4'>
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                            <CardDescription>
                                What the panel last heard from this anchor.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className='flex flex-col gap-4'>
                            <AnchorStatusCell anchor={anchor} />
                            <dl className='flex flex-col gap-3'>
                                <Detail label='Role'>
                                    <span className='capitalize'>
                                        {anchor.mode}
                                    </span>
                                </Detail>
                                <Detail label='Version'>
                                    <span className='font-mono text-sm'>
                                        {anchor.version ?? '—'}
                                    </span>
                                </Detail>
                                <Detail label='Protocol'>
                                    <AnchorProtocol anchor={anchor} />
                                </Detail>
                                <Detail label='Enrolled'>
                                    {when(anchor.enrolledAt)}
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Reachability</CardTitle>
                            <CardDescription>
                                How the panel and this anchor find each other.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className='flex flex-col gap-3'>
                                <Detail label='Connection URL' wrap>
                                    <span className='font-mono text-sm break-all'>
                                        {anchor.publicUrl}
                                    </span>
                                </Detail>
                                <Detail label='Reaches the panel at' wrap>
                                    <span className='font-mono text-sm break-all'>
                                        {anchor.panelUrl}
                                    </span>
                                    {anchor.panelUrlOverride === null && (
                                        <span className='text-muted-foreground'>
                                            {' '}
                                            (panel default)
                                        </span>
                                    )}
                                </Detail>
                                <Detail label='Route'>
                                    {anchor.relayName ? (
                                        <>
                                            via{' '}
                                            <Link
                                                to='/admin/anchors/$anchorId'
                                                params={{
                                                    anchorId: String(
                                                        anchor.relayId
                                                    ),
                                                }}
                                                className={cn(
                                                    buttonVariants({
                                                        variant: 'link',
                                                    }),
                                                    'h-auto p-0'
                                                )}
                                            >
                                                {anchor.relayName}
                                            </Link>
                                        </>
                                    ) : (
                                        'Direct connection'
                                    )}
                                </Detail>
                                {/* Beside the URLs rather than beside the
                                    status: what an anchor can carry is a
                                    property of the connection, not of its
                                    health -- and it evens the two cards out. */}
                                <Detail label='Capabilities' wrap>
                                    {anchor.capabilities.length > 0
                                        ? anchor.capabilities.join(', ')
                                        : 'None reported'}
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Delete this anchor</CardTitle>
                            <CardDescription>
                                Its installation secret goes with it, so the
                                daemon has to be re-enrolled to come back.
                            </CardDescription>
                        </CardHeader>
                        {/* The action belongs in the ruled footer bar, not
                            loose in the content (docs/card-design.md rule 4). */}
                        <CardFooter>
                            <Button
                                variant='destructive'
                                className='w-full'
                                loading={deleteMutation.isPending}
                                onClick={handleDelete}
                            >
                                Delete anchor
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </div>

            <AnchorFormDialog
                anchor={editing}
                anchors={fleet?.items ?? []}
                close={() => setEditing(null)}
                refresh={refresh}
            />

            <ResponsiveDialog
                open={installing}
                onOpenChange={open => !open && setInstalling(false)}
            >
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            Install on {anchor.name}
                        </ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            Run this on the machine.
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    {installing && (
                        <EnrollmentPanel
                            anchor={anchor}
                            refresh={refresh}
                            onClose={() => setInstalling(false)}
                        />
                    )}
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/anchors/$anchorId'
)({
    component: AnchorDetailPage,
})
