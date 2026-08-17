import LedgerRuleRow from '@/features/servers/firewall/components/LedgerRuleRow.tsx'
import {
    type FirewallPolicy,
    type FirewallRule,
    type RuleDirection,
    describeChain,
    describePolicyRow,
    directionLabels,
    policyAdjectives,
} from '@/features/servers/firewall/api.ts'
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
import { IconArrowDown, IconArrowUp, IconPlus } from '@tabler/icons-react'
import { useEffect, useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { Switch } from '@/components/ui/Switch'
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

const policyItems = (
    Object.entries(policyAdjectives) as [FirewallPolicy, string][]
).map(([value, label]) => ({ value, label }))

interface Props {
    direction: RuleDirection
    /** This chain's rules, in evaluation order, keeping their global positions. */
    rules: FirewallRule[]
    policy: FirewallPolicy
    isLoggingUnmatched: boolean
    /** Options have not loaded, so the policy controls have nothing to show. */
    isPending: boolean
    /** A policy or logging write is in flight. */
    isSaving: boolean
    isMutating: boolean
    onAdd: () => void
    onEdit: (rule: FirewallRule) => void
    onDelete: (rule: FirewallRule) => void
    onToggle: (rule: FirewallRule, enabled: boolean) => void
    onReorder: (from: number, to: number, digest: string | null) => void
    onPolicyChange: (policy: FirewallPolicy) => void
    onLoggingChange: (enabled: boolean) => void
}

/**
 * One firewall chain: its rules in evaluation order, and the policy every
 * packet that survives them falls into.
 *
 * The policy is deliberately the last line of the chain rather than a control
 * in a settings card somewhere above. It *is* the terminal rule -- what happens
 * to traffic no rule above matched -- and drawing it anywhere else forced the
 * page to explain in a footnote what it can simply show.
 */
const ChainSection = ({
    direction,
    rules,
    policy,
    isLoggingUnmatched,
    isPending,
    isSaving,
    isMutating,
    onAdd,
    onEdit,
    onDelete,
    onToggle,
    onReorder,
    onPolicyChange,
    onLoggingChange,
}: Props) => {
    /*
     * Both controls below save the moment they change, and their displayed
     * value is whatever Proxmox last reported -- so between the click and the
     * refetch they would sit showing the old value, which reads as the change
     * not having registered. The draft covers that gap, and is dropped again
     * the moment the write settles: on success the refetched value replaces it,
     * and on failure the control snaps back to what is actually configured.
     */
    const [draftPolicy, setDraftPolicy] = useState<FirewallPolicy | null>(null)
    const [draftLogging, setDraftLogging] = useState<boolean | null>(null)

    useEffect(() => {
        if (isSaving) return

        setDraftPolicy(null)
        setDraftLogging(null)
    }, [isSaving])

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    )

    // Each chain owns its own drag context, so a rule can never be dropped into
    // the other one. Dragging across directions would be meaningless -- the two
    // chains are matched separately -- but the combined list has a single index
    // space, so it would happily renumber everything for no effect.
    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) return

        const from = Number(active.id)

        onReorder(
            from,
            Number(over.id),
            rules.find(rule => rule.position === from)?.digest ?? null
        )
    }

    const DirectionIcon = direction === 'in' ? IconArrowDown : IconArrowUp

    return (
        <section>
            <div
                className={
                    'flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3'
                }
            >
                <h3 className={'flex items-center gap-1.5 font-medium'}>
                    <DirectionIcon
                        className={'size-4 text-muted-foreground'}
                        aria-hidden
                    />
                    {directionLabels[direction]}
                </h3>
                <span className={'text-xs text-muted-foreground'}>
                    {describeChain(rules)}
                </span>
                <span className={'flex-1'} />
                <Button variant={'outline'} size={'sm'} onClick={onAdd}>
                    <IconPlus className={'size-4'} />
                    Add rule
                </Button>
            </div>

            {rules.length > 0 && (
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragEnd={onDragEnd}
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className={'w-8 pl-5'}>
                                    <span className={'sr-only'}>Reorder</span>
                                </TableHead>
                                <TableHead className={'w-10'}>#</TableHead>
                                <TableHead className={'w-12'}>On</TableHead>
                                <TableHead className={'w-24'}>Action</TableHead>
                                <TableHead className={'w-40'}>Service</TableHead>
                                {/* The chain says which end of the connection
                                    this is, so the column can name the far end
                                    outright instead of shipping two mostly
                                    empty address columns. */}
                                <TableHead className={'w-48'}>
                                    {direction === 'in'
                                        ? 'Source'
                                        : 'Destination'}
                                </TableHead>
                                <TableHead>Comment</TableHead>
                                <TableHead className={'w-10'}>
                                    <span className={'sr-only'}>Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <SortableContext
                                items={rules.map(rule => rule.position!)}
                                strategy={verticalListSortingStrategy}
                            >
                                {rules.map((rule, index) => (
                                    <LedgerRuleRow
                                        key={rule.position}
                                        rule={rule}
                                        ordinal={index + 1}
                                        isMutating={isMutating}
                                        onEdit={() => onEdit(rule)}
                                        onDelete={() => onDelete(rule)}
                                        onToggle={enabled =>
                                            onToggle(rule, enabled)
                                        }
                                    />
                                ))}
                            </SortableContext>
                        </TableBody>
                    </Table>
                </DndContext>
            )}

            <div
                className={
                    'flex flex-wrap items-center gap-x-3 gap-y-2 border-t bg-muted/30 px-4 py-3 text-sm'
                }
            >
                <span className={'text-muted-foreground'}>
                    {describePolicyRow(direction, rules.length > 0)}
                </span>
                <Select
                    items={policyItems}
                    value={draftPolicy ?? policy}
                    disabled={isPending || isSaving}
                    onValueChange={value => {
                        setDraftPolicy(value as FirewallPolicy)
                        onPolicyChange(value as FirewallPolicy)
                    }}
                >
                    <SelectTrigger
                        className={'h-8'}
                        aria-label={`Default policy for ${directionLabels[direction].toLowerCase()} traffic`}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {policyItems.map(item => (
                            <SelectItem key={item.value} value={item.value}>
                                {item.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <span className={'flex-1'} />

                {/* Proxmox logs the packets that reach the default policy, so
                    this switch belongs beside the policy it records rather than
                    in the activity list it fills. */}
                <label
                    className={
                        'flex items-center gap-2 text-xs text-muted-foreground'
                    }
                >
                    Log unmatched traffic
                    <Switch
                        checked={draftLogging ?? isLoggingUnmatched}
                        disabled={isPending || isSaving}
                        onCheckedChange={enabled => {
                            setDraftLogging(enabled)
                            onLoggingChange(enabled)
                        }}
                    />
                </label>
            </div>
        </section>
    )
}

export default ChainSection
