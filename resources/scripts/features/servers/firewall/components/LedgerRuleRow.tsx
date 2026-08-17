import {
    type FirewallRule,
    describeRuleTraffic,
} from '@/features/servers/firewall/api.ts'
import { cn } from '@/utils'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { IconDots, IconGripVertical } from '@tabler/icons-react'

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
 * Action is semantic colour on the row's edge and on the word itself, never a
 * filled badge. Accept is the overwhelmingly common case, so rendering it in
 * the brand accent put the loudest thing on the page on the one value that
 * carries no information; and blue is reserved for "this is a control".
 *
 * Reject shares destructive with drop at lower emphasis, because it also stops
 * the packet -- it is just polite enough to say so.
 */
const actionStyles = {
    ACCEPT: { edge: 'border-l-success', text: 'text-success' },
    DROP: { edge: 'border-l-destructive', text: 'text-destructive' },
    REJECT: { edge: 'border-l-destructive/50', text: 'text-destructive/80' },
} as const

/** A field the user left unconstrained, in the ruleset's own vocabulary. */
const Any = () => <span className={'text-muted-foreground'}>any</span>

interface Props {
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
}

const LedgerRuleRow = ({
    rule,
    ordinal,
    onEdit,
    onDelete,
    onToggle,
    isMutating,
}: Props) => {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
        useSortable({ id: rule.position! })

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
        <TableRow
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'font-mono text-xs',
                isDragging && 'relative z-10 opacity-50',
                // A switched-off rule has to stop looking enforced -- edge
                // included. Greying only the text left a full-strength DROP
                // sitting next to a switch that said it was off.
                !rule.isEnabled && 'opacity-60'
            )}
        >
            {/* The row's action edge rides on the first cell: a border on the
                <tr> itself only paints under border-collapse, which the table
                does not promise. */}
            <TableCell
                className={cn(
                    'w-8 border-l-2 pl-3',
                    rule.isEnabled ? style.edge : 'border-l-border'
                )}
            >
                {/* Listeners live on the handle, not the row: on the row every
                    pointerdown starts a drag, which makes the switch and the
                    menu unclickable. */}
                <button
                    type={'button'}
                    className={
                        'cursor-grab text-muted-foreground/50 hover:text-muted-foreground focus-visible:outline-2 focus-visible:outline-ring'
                    }
                    aria-label={`Reorder rule ${ordinal}`}
                    {...attributes}
                    {...listeners}
                >
                    <IconGripVertical className={'size-4'} />
                </button>
            </TableCell>

            <TableCell className={'w-10 text-muted-foreground tabular-nums'}>
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
                    rule.isEnabled ? style.text : 'text-muted-foreground'
                )}
            >
                {rule.action}
            </TableCell>

            <TableCell className={'w-40'}>{traffic ?? <Any />}</TableCell>

            <TableCell className={'w-48'}>
                {peer ?? <Any />}
                {nearEnd && (
                    <span className={'text-muted-foreground'}> → {nearEnd}</span>
                )}
            </TableCell>

            <TableCell
                className={'max-w-0 truncate font-sans text-muted-foreground'}
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
                        <DropdownMenuItem onClick={onEdit}>Edit</DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={onDelete}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </TableCell>
        </TableRow>
    )
}

export default LedgerRuleRow
