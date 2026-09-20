import { serverQueries } from '@/features/servers/admin/api.ts'
import {
    type MigrationAddress,
    type MigrationCandidate,
    migrateServer,
    migrationQueries,
    useMigrationPlan,
    useMigrationPreview,
} from '@/features/servers/admin/migration.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { type Server, ServerLifecycle } from '@/types/server.ts'
import {
    IconArrowRight,
    IconExclamationCircle,
    IconServer2,
} from '@tabler/icons-react'
import { useEffect, useId, useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import { RadioGroup, RadioGroupItem } from '@/components/ui/RadioGroup'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

interface Props {
    server: Server
}

/** The verdict as a chip: the same three words every row uses. */
const VerdictBadge = ({ candidate }: { candidate: MigrationCandidate }) => {
    if (candidate.disposition === 'preserve') {
        return <Badge variant={'secondary'}>Keeps its addresses</Badge>
    }

    if (candidate.disposition === 'reallocate') {
        return <Badge variant={'outline'}>Gets new addresses</Badge>
    }

    return <Badge variant={'outline'}>Unavailable</Badge>
}

const AddressList = ({ addresses }: { addresses: MigrationAddress[] }) => (
    <ul className={'space-y-0.5 font-mono text-xs'}>
        {addresses.map(address => (
            <li key={address.id}>
                {address.ip}/{address.prefixLength}
                <span className={'text-muted-foreground ml-2 font-sans'}>
                    {address.poolName}
                </span>
            </li>
        ))}
    </ul>
)

const NodeChoice = ({ candidate }: { candidate: MigrationCandidate }) => {
    const id = useId()
    const blocked = candidate.disposition === 'blocked'

    return (
        <FieldLabel htmlFor={id} data-disabled={blocked}>
            <Field orientation={'horizontal'}>
                <RadioGroupItem
                    id={id}
                    value={String(candidate.nodeId)}
                    disabled={blocked}
                />
                <FieldContent>
                    <FieldTitle>
                        {candidate.nodeName}
                        <VerdictBadge candidate={candidate} />
                    </FieldTitle>
                    <FieldDescription>
                        {candidate.blockedReason ??
                            [
                                candidate.locationName,
                                candidate.bridgeName,
                                candidate.canMigrateOnline
                                    ? 'Moves without stopping'
                                    : 'Stops and restarts',
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                    </FieldDescription>
                </FieldContent>
            </Field>
        </FieldLabel>
    )
}

/**
 * Picks a destination node and says what it does to the server's addresses
 * before anything is committed.
 *
 * The disposition is the panel's answer, not the operator's: Convoy records
 * which pools a bridge fronts, so "the IP follows" is a question it can answer,
 * and a reallocation is shown as the concrete addresses the server would lose
 * and gain rather than as a warning to interpret.
 */
const MigrateServerModal = ({ server }: Props) => {
    const [open, setOpen] = useState(false)
    const [nodeId, setNodeId] = useState<number | null>(null)
    // An install, a restore or another migration owns the guest's config while
    // it runs; the API refuses anyway, and a button that always errors is worse
    // than one that is plainly unavailable.
    const busy =
        server.lifecycle === ServerLifecycle.Installing ||
        server.lifecycle === ServerLifecycle.RestoringBackup ||
        server.lifecycle === ServerLifecycle.Migrating ||
        server.lifecycle === ServerLifecycle.Deleting
    const [acknowledged, setAcknowledged] = useState(false)
    const acknowledgeId = useId()

    const {
        data: plan,
        isPending,
        isError,
    } = useMigrationPlan(open ? server.uuid : null)

    const candidate = plan?.candidates.find(row => row.nodeId === nodeId)
    const reallocates = candidate?.disposition === 'reallocate'

    // Only a reallocation has addresses to resolve, and resolving them costs a
    // second preflight call to the node. A preserving pick already knows
    // everything it needs from the plan.
    const { data: preview, isFetching: previewing } = useMigrationPreview(
        open && reallocates ? server.uuid : null,
        reallocates ? nodeId : null
    )

    // Every pick is a different set of addresses, so the acknowledgement it
    // covers is gone the moment the destination changes.
    useEffect(() => setAcknowledged(false), [nodeId])

    const short = reallocates && preview?.isShortOnAddresses === true
    const ready =
        candidate !== undefined &&
        candidate.disposition !== 'blocked' &&
        !short &&
        (!reallocates || (acknowledged && !previewing))

    const [state, submit] = useAsyncFunction(async () => {
        if (nodeId === null) return

        try {
            await migrateServer(server.uuid, nodeId, acknowledged)

            await queryClient.invalidateQueries({
                queryKey: serverQueries.all(),
            })
            await queryClient.invalidateQueries({
                queryKey: migrationQueries.all(),
            })

            toast.add({ title: 'Migration started', type: 'success' })
            setOpen(false)
            setNodeId(null)
        } catch (e) {
            toast.add({
                title: 'Could not start the migration',
                description:
                    e instanceof Error
                        ? e.message
                        : 'Proxmox refused the move.',
                type: 'error',
            })
            throw e
        }
    })

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={next => {
                setOpen(next)
                if (!next) setNodeId(null)
            }}
        >
            <ResponsiveDialogTrigger
                render={
                    <Button variant={'outline'} disabled={busy}>
                        <IconArrowRight className={'size-4'} />
                        Migrate
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Migrate {server.name}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody className={'space-y-4'}>
                    {isPending && (
                        <div className={'space-y-2'}>
                            <Skeleton className={'h-16 w-full'} />
                            <Skeleton className={'h-16 w-full'} />
                        </div>
                    )}

                    {isError && (
                        <Alert variant={'destructive'}>
                            <IconExclamationCircle className={'size-4'} />
                            <AlertDescription>
                                Proxmox could not be asked where this server can
                                go. Check the node is reachable and try again.
                            </AlertDescription>
                        </Alert>
                    )}

                    {plan?.emptyReason && (
                        <SimpleEmptyState
                            className={'py-6'}
                            icon={IconServer2}
                            title={'Nowhere to migrate to'}
                            description={plan.emptyReason}
                        />
                    )}

                    {plan && plan.localResources.length > 0 && (
                        <Alert variant={'destructive'}>
                            <IconExclamationCircle className={'size-4'} />
                            <AlertDescription>
                                Proxmox will not move this guest while{' '}
                                {plan.localResources.join(', ')} is passed
                                through to it.
                            </AlertDescription>
                        </Alert>
                    )}

                    {plan && plan.candidates.length > 0 && (
                        <RadioGroup
                            className={'gap-2'}
                            value={nodeId === null ? '' : String(nodeId)}
                            onValueChange={value => setNodeId(Number(value))}
                        >
                            {plan.candidates.map(row => (
                                <NodeChoice key={row.nodeId} candidate={row} />
                            ))}
                        </RadioGroup>
                    )}

                    {reallocates && previewing && (
                        <Skeleton className={'h-24 w-full'} />
                    )}

                    {reallocates && preview && !previewing && (
                        <div
                            className={
                                'space-y-3 rounded-lg border p-3 text-sm'
                            }
                        >
                            {short ? (
                                <p className={'text-destructive'}>
                                    {candidate?.nodeName} no longer has enough
                                    free addresses to replace this server's.
                                </p>
                            ) : (
                                <>
                                    <dl className={'space-y-2'}>
                                        <dt
                                            className={
                                                'text-muted-foreground text-xs'
                                            }
                                        >
                                            Releases
                                        </dt>
                                        <dd>
                                            <AddressList
                                                addresses={preview.released}
                                            />
                                        </dd>
                                        <dt
                                            className={
                                                'text-muted-foreground text-xs'
                                            }
                                        >
                                            Gets
                                        </dt>
                                        <dd>
                                            <AddressList
                                                addresses={preview.allocated}
                                            />
                                        </dd>
                                    </dl>
                                    <Field orientation={'horizontal'}>
                                        <Checkbox
                                            id={acknowledgeId}
                                            checked={acknowledged}
                                            onCheckedChange={next =>
                                                setAcknowledged(Boolean(next))
                                            }
                                        />
                                        <FieldContent>
                                            <FieldLabel htmlFor={acknowledgeId}>
                                                The guest's IP address changes
                                            </FieldLabel>
                                            <FieldDescription>
                                                It is stopped, moved, and
                                                started again on the new
                                                address. Anything pointing at
                                                the old one stops working.
                                            </FieldDescription>
                                        </FieldContent>
                                    </Field>
                                </>
                            )}
                        </div>
                    )}

                    {plan && plan.candidates.length > 0 && (
                        <p className={'text-muted-foreground text-xs'}>
                            Cluster members only. Moving a guest to another
                            cluster is not supported.
                        </p>
                    )}
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'} type={'button'}>
                                Cancel
                            </Button>
                        }
                    />
                    <Button
                        disabled={!ready || state.loading}
                        loading={state.loading}
                        onClick={() => submit()}
                    >
                        Migrate
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default MigrateServerModal
