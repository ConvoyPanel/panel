import {
    type FirewallRule,
    describeRuleTraffic,
} from '@/features/servers/firewall/api.ts'
import { cn } from '@/utils'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { IconDots, IconGripVertical } from '@tabler/icons-react'
import type { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { Switch } from '@/components/ui/Switch'
import { TableCell, TableRow } from '@/components/ui/Table'

/*
 * Action is semantic colour on the word itself, never a filled badge and no
 * longer a stripe down the row's edge -- the word already carries it, and the
 * stripe was a second, louder copy of the same fact. Accept is the
 * overwhelmingly common case, so rendering it in the brand accent put the
 * loudest thing on the page on the one value that carries no information; and
 * blue is reserved for "this is a control".
 *
 * Reject shares destructive with drop at lower emphasis, because it also stops
 * the packet -- it is just polite enough to say so.
 */
const actionStyles = {
    ACCEPT: 'text-success',
    DROP: 'text-destructive',
    REJECT: 'text-destructive/80',
} as const

/** A field the user left unconstrained, in the ruleset's own vocabulary. */
const Any = () => <span className={'text-muted-foreground'}>any</span>

interface CellProps {
    rule: FirewallRule
    /**
     * The rule's place in its own chain, counting from one. Deliberately not
     * `position`, which is its index in Proxmox's combined list: showing that
     * numbers the outbound chain 5, 6, 7 under an inbound chain ending at 4,
     * which reads as one continuous sequence -- the exact thing this layout
     * exists to stop implying.
     */
    ordinal: number
    onEdit: () => void
    onDelete: () => void
    onToggle: (enabled: boolean) => void
    isMutating: boolean
    /** The grip, wired for the row in the table and inert for the floating copy. */
    handle: ReactNode
}

type Props = Omit<CellProps, 'handle'>

/** Lets the chain find the row it is about to lift a copy out of, to measure it. */
export const ruleRowId = (position: number) => `rule-row-${position}`

const RuleCells = ({
    rule,
    ordinal,
    onEdit,
    onDelete,
    onToggle,
    isMutating,
    handle,
}: CellProps) => {
    const traffic = describeRuleTraffic(rule)
    const style = actionStyles[rule.action]

    /*
     * The chain already states the direction, so the address column shows the
     * far end of the connection: where inbound traffic came from, or where
     * outbound traffic is going. The near end is almost always this server and
     * printing it would just repeat the chain -- but it *can* be set, so when
     * it is, it trails the far end rather than disappearing.
     */
    const [peer, nearEnd] =
        rule.direction === 'in'
            ? [rule.sourceAddress, rule.destinationAddress]
            : [rule.destinationAddress, rule.sourceAddress]

    return (
        <>
            <TableCell className={'w-8 pl-5'}>{handle}</TableCell>

            <TableCell className={'text-muted-foreground w-10 tabular-nums'}>
                {ordinal}
            </TableCell>

            <TableCell className={'w-12'}>
                <Switch
                    checked={rule.isEnabled}
                    disabled={isMutating}
                    onCheckedChange={onToggle}
                    aria-label={
                        rule.isEnabled ? 'Turn rule off' : 'Turn rule on'
                    }
                />
            </TableCell>

            <TableCell
                className={cn(
                    'w-24 font-semibold tracking-wide',
                    rule.isEnabled ? style : 'text-muted-foreground'
                )}
            >
                {rule.action}
            </TableCell>

            <TableCell className={'w-40'}>{traffic ?? <Any />}</TableCell>

            <TableCell className={'w-48'}>
                {peer ?? <Any />}
                {nearEnd && (
                    <span className={'text-muted-foreground'}>
                        {' '}
                        → {nearEnd}
                    </span>
                )}
            </TableCell>

            <TableCell
                className={'text-muted-foreground max-w-0 truncate font-sans'}
                title={rule.comment ?? undefined}
            >
                {rule.comment}
            </TableCell>

            <TableCell className={'w-10 text-right'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant={'ghost'}
                            size={'icon'}
                            aria-label={`Actions for rule ${ordinal}`}
                        >
                            <IconDots className={'size-4'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align={'end'}>
                        <DropdownMenuItem onClick={onEdit}>
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={onDelete}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </TableCell>
        </>
    )
}

const gripClasses =
    'text-muted-foreground/50 hover:text-muted-foreground focus-visible:outline-ring cursor-grab focus-visible:outline-2'

/** One rule in its chain, in the order the packets meet it. */
const LedgerRuleRow = ({ rule, ordinal, ...cells }: Props) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        // Held still while a move is in flight: the optimistic order carries
        // pre-move positions, and a second drag would send one of them as the
        // rule to move.
        id: rule.position!,
        disabled: cells.isMutating,
        // The rule is already where it was dropped by the time the pointer is
        // released. dnd-kit's default is to FLIP it there anyway -- inverse
        // transforms on every row it displaced, animated to zero over ~180ms --
        // which replays the move a second time after the fact.
        animateLayoutChanges: () => false,
    })

    return (
        <TableRow
            ref={setNodeRef}
            id={ruleRowId(rule.position!)}
            // Translate, not Transform: the sortable strategy only ever moves
            // rows, so the scale half of the transform is a no-op that rides
            // along on every row's inline style for the length of the drag.
            style={{ transform: CSS.Translate.toString(transform), transition }}
            className={cn(
                'font-mono text-xs',
                // The floating copy is the one being moved, so this is the hole
                // it came out of -- kept in place and dimmed so the slot it will
                // drop into stays readable.
                isDragging && 'opacity-40',
                // A switched-off rule has to stop looking enforced. Greying only
                // the action left a full-strength DROP sitting next to a switch
                // that said it was off.
                !rule.isEnabled && 'opacity-60'
            )}
        >
            <RuleCells
                rule={rule}
                ordinal={ordinal}
                {...cells}
                handle={
                    // Listeners live on the handle, not the row: on the row
                    // every pointerdown starts a drag, which makes the switch
                    // and the menu unclickable.
                    <button
                        type={'button'}
                        className={gripClasses}
                        aria-label={`Reorder rule ${ordinal}`}
                        {...attributes}
                        {...listeners}
                    >
                        <IconGripVertical className={'size-4'} />
                    </button>
                }
            />
        </TableRow>
    )
}

/**
 * The copy that follows the pointer.
 *
 * It carries no sortable registration -- the row it was lifted out of still
 * holds that -- so it is free to go anywhere on the page while the chain below
 * keeps sorting itself.
 */
export const LedgerRuleRowOverlay = ({ rule, ordinal, ...cells }: Props) => (
    <TableRow
        // Chrome lives on the table around it -- a bare `tr` paints a
        // background unevenly and drops a ring outright. Hover is off because
        // the pointer is by definition on top of this one.
        className={cn(
            'font-mono text-xs hover:bg-transparent [&>td]:border-0',
            !rule.isEnabled && 'opacity-60'
        )}
    >
        <RuleCells
            rule={rule}
            ordinal={ordinal}
            {...cells}
            handle={
                <span className={cn(gripClasses, 'cursor-grabbing')}>
                    <IconGripVertical className={'size-4'} />
                </span>
            }
        />
    </TableRow>
)

export default LedgerRuleRow
