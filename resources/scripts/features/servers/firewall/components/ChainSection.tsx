import {
    type FirewallPolicy,
    type FirewallRule,
    type RuleDirection,
    describeChain,
    describePolicyRow,
    directionLabels,
    policyAdjectives,
} from '@/features/servers/firewall/api.ts'
import LedgerRuleRow, {
    LedgerRuleRowOverlay,
    ruleRowId,
} from '@/features/servers/firewall/components/LedgerRuleRow.tsx'
import {
    DndContext,
    type DragEndEvent,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core'
import {
    SortableContext,
    arrayMove,
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

/**
 * The column widths of the row being dragged, so the floating copy keeps the
 * table's proportions once it is out of the table.
 */
const measureColumns = (row: HTMLElement | null) =>
    row
        ? [...row.querySelectorAll('td')].map(
              c => c.getBoundingClientRect().width
          )
        : []

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

    const [dragging, setDragging] = useState<{
        active: number | null
        over: number | null
        columns: number[]
    }>({ active: null, over: null, columns: [] })

    // Each chain owns its own drag context, so a rule can never be dropped into
    // the other one. Dragging across directions would be meaningless -- the two
    // chains are matched separately -- but the combined list has a single index
    // space, so it would happily renumber everything for no effect.
    const onDragEnd = ({ active, over, delta }: DragEndEvent) => {
        setDragging({ active: null, over: null, columns: [] })

        const from = Number(active.id)
        const fromIndex = rules.findIndex(rule => rule.position === from)

        if (fromIndex === -1) return

        // A drop that lands off the rows resolves to nothing, and reverting it
        // reads as the handle being broken -- so it goes to whichever end of the
        // chain it was headed for.
        const overIndex = over
            ? rules.findIndex(rule => rule.position === Number(over.id))
            : -1
        const toIndex =
            overIndex === -1 ? (delta.y > 0 ? rules.length - 1 : 0) : overIndex

        if (toIndex === fromIndex) return

        onReorder(
            from,
            rules[toIndex].position!,
            rules[fromIndex].digest ?? null
        )
    }

    const activeRule = rules.find(rule => rule.position === dragging.active)

    /*
     * Ordinals read from where the rows have *moved to*. Numbering them by the
     * saved order while they sit somewhere else labels every one of them wrong
     * until the drop lands and the refetch catches up.
     */
    const preview = (() => {
        if (dragging.active === null || dragging.over === null) return rules

        const from = rules.findIndex(rule => rule.position === dragging.active)
        const to = rules.findIndex(rule => rule.position === dragging.over)

        return from === -1 || to === -1 ? rules : arrayMove(rules, from, to)
    })()

    const ordinalOf = (rule: FirewallRule) =>
        preview.findIndex(candidate => candidate.position === rule.position) + 1

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
                        className={'text-muted-foreground size-4'}
                        aria-hidden
                    />
                    {directionLabels[direction]}
                </h3>
                <span className={'text-muted-foreground text-xs'}>
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
                    // The page may scroll if a rule is dragged past the
                    // viewport; the table's own overflow container may not.
                    // This table is wide enough to scroll sideways, and sliding
                    // it out from under the pointer is what makes a drag feel
                    // trapped in its box.
                    autoScroll={{
                        canScroll: element =>
                            element === document.scrollingElement ||
                            element === document.documentElement,
                    }}
                    onDragStart={({ active }) =>
                        setDragging({
                            active: Number(active.id),
                            over: Number(active.id),
                            columns: measureColumns(
                                document.getElementById(
                                    ruleRowId(Number(active.id))
                                )
                            ),
                        })
                    }
                    onDragOver={({ active, over }) =>
                        setDragging(current => ({
                            ...current,
                            active: Number(active.id),
                            over: over ? Number(over.id) : null,
                        }))
                    }
                    onDragCancel={() =>
                        setDragging({ active: null, over: null, columns: [] })
                    }
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
                                <TableHead className={'w-40'}>
                                    Service
                                </TableHead>
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
                                {rules.map(rule => (
                                    <LedgerRuleRow
                                        key={rule.position}
                                        rule={rule}
                                        ordinal={ordinalOf(rule)}
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

                    {/* The row travels in here rather than in the table, so it
                        follows the pointer instead of being held to the rows it
                        came from. It needs a table of its own -- a lone `tr` has
                        no columns to size against -- with the widths measured
                        off the row it was lifted from. */}
                    <DragOverlay>
                        {activeRule && (
                            <table
                                className={
                                    'bg-card ring-foreground/10 [table-layout:fixed] cursor-grabbing overflow-hidden rounded-lg text-sm shadow-lg ring-1'
                                }
                            >
                                <colgroup>
                                    {dragging.columns.map((width, index) => (
                                        <col key={index} style={{ width }} />
                                    ))}
                                </colgroup>
                                <tbody>
                                    <LedgerRuleRowOverlay
                                        rule={activeRule}
                                        ordinal={ordinalOf(activeRule)}
                                        isMutating={isMutating}
                                        onEdit={() => {}}
                                        onDelete={() => {}}
                                        onToggle={() => {}}
                                    />
                                </tbody>
                            </table>
                        )}
                    </DragOverlay>
                </DndContext>
            )}

            <div
                className={
                    'bg-muted/30 flex flex-wrap items-center gap-x-3 gap-y-2 border-t px-4 py-3 text-sm'
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
                        'text-muted-foreground flex items-center gap-2 text-xs'
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
