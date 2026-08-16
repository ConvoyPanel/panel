import SortableRuleRow from '@/features/servers/firewall/components/SortableRuleRow.tsx'
import {
    type FirewallRule,
    deleteRule,
    firewallQueries,
    moveRule,
    ruleToFormValues,
    updateRule,
    useFirewallOptions,
    useFirewallRules,
} from '@/features/servers/firewall/api.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import {
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core'
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { IconPlus, IconShieldHalf } from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
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
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { toast } from '@/components/ui/Toast'

import RuleFormDialog from './RuleFormDialog.tsx'

interface Props {
    uuid: string
}

const RulesCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: rules, isLoading } = useFirewallRules(uuid)
    const { data: options } = useFirewallOptions(uuid)

    const [dialogOpen, setDialogOpen] = useState(false)
    const [editing, setEditing] = useState<FirewallRule | undefined>()

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    )

    // Every write renumbers positions, so nothing is patched into the cache --
    // the list is refetched and the server's ordering wins.
    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: firewallQueries.all(uuid) })

    const { mutate: reorder, isPending: isReordering } = useMutation({
        mutationFn: ({ from, to, digest }: { from: number; to: number; digest: string | null }) =>
            moveRule(uuid, from, to, digest),
        onSuccess: refresh,
        onError: e => {
            refresh()
            toast.add({
                title: getApiErrorMessage(e, 'Failed to reorder the rules'),
                type: 'error',
            })
        },
    })

    const { mutate: toggle, isPending: isToggling } = useMutation({
        mutationFn: ({ rule, enabled }: { rule: FirewallRule; enabled: boolean }) =>
            updateRule(uuid, rule.position!, {
                ...ruleToFormValues(rule),
                enabled,
            }),
        onSuccess: refresh,
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to update the rule'),
                type: 'error',
            }),
    })

    const { mutate: remove } = useMutation({
        mutationFn: (rule: FirewallRule) =>
            deleteRule(uuid, rule.position!, rule.digest),
        onSuccess: () => {
            refresh()
            toast.add({ title: 'Rule deleted', type: 'success' })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to delete the rule'),
                type: 'error',
            }),
    })

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) return

        const from = Number(active.id)

        reorder({
            from,
            to: Number(over.id),
            digest: rules?.find(rule => rule.position === from)?.digest ?? null,
        })
    }

    const onDelete = async (rule: FirewallRule) => {
        const confirmed = await confirm({
            title: 'Delete rule',
            description: rule.comment
                ? `"${rule.comment}" will stop being enforced immediately.`
                : 'This rule will stop being enforced immediately.',
            confirmText: 'Delete',
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) remove(rule)
    }

    const openCreate = () => {
        setEditing(undefined)
        setDialogOpen(true)
    }

    const openEdit = (rule: FirewallRule) => {
        setEditing(rule)
        setDialogOpen(true)
    }

    const inbound = options?.inboundPolicy ?? 'ACCEPT'
    const outbound = options?.outboundPolicy ?? 'ACCEPT'
    const verb = { ACCEPT: 'accepted', DROP: 'dropped', REJECT: 'rejected' }

    return (
        <Card>
            <CardHeader>
                <CardTitle className={'flex items-center gap-2'}>
                    Rules
                    {options && !options.isEnabled && (
                        <Badge variant={'secondary'}>Not enforced</Badge>
                    )}
                </CardTitle>
                <CardDescription>
                    Checked from the top down — the first rule that matches
                    decides, and the rest are skipped.
                </CardDescription>
                <CardAction>
                    <Button onClick={openCreate}>
                        <IconPlus className={'size-4'} />
                        Add rule
                    </Button>
                </CardAction>
            </CardHeader>

            <CardContent className={'flex-1 px-0'}>
                {isLoading ? (
                    <Skeleton className={'mx-4 h-40'} />
                ) : !rules?.length ? (
                    <SimpleEmptyState
                        icon={IconShieldHalf}
                        title={'No rules yet'}
                        description={
                            inbound === 'ACCEPT'
                                ? 'All inbound traffic is currently allowed. Add a rule to start restricting it.'
                                : 'Inbound traffic is being blocked. Add a rule to let something through.'
                        }
                        action={
                            <Button variant={'outline'} onClick={openCreate}>
                                Add rule
                            </Button>
                        }
                    />
                ) : (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragEnd={onDragEnd}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className={'w-9'}>
                                        <span className={'sr-only'}>
                                            Reorder
                                        </span>
                                    </TableHead>
                                    <TableHead className={'w-8'}>#</TableHead>
                                    <TableHead className={'w-12'}>On</TableHead>
                                    <TableHead>Direction</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Protocol / port</TableHead>
                                    <TableHead>Source</TableHead>
                                    <TableHead>Destination</TableHead>
                                    <TableHead>Comment</TableHead>
                                    <TableHead className={'w-10'}>
                                        <span className={'sr-only'}>
                                            Actions
                                        </span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <SortableContext
                                    items={rules.map(rule => rule.position!)}
                                    strategy={verticalListSortingStrategy}
                                >
                                    {rules.map(rule => (
                                        <SortableRuleRow
                                            key={rule.position}
                                            rule={rule}
                                            isMutating={
                                                isReordering || isToggling
                                            }
                                            onEdit={() => openEdit(rule)}
                                            onDelete={() => onDelete(rule)}
                                            onToggle={enabled =>
                                                toggle({ rule, enabled })
                                            }
                                        />
                                    ))}
                                </SortableContext>
                            </TableBody>
                        </Table>
                    </DndContext>
                )}
            </CardContent>

            {options && (
                <CardFooter className={'text-xs text-muted-foreground'}>
                    Anything not matched above is {verb[inbound]} inbound and{' '}
                    {verb[outbound]} outbound.
                </CardFooter>
            )}

            <RuleFormDialog
                uuid={uuid}
                rule={editing}
                position={editing ? undefined : rules?.length}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </Card>
    )
}

export default RulesCard
