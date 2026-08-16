import {
    type FirewallRule,
    describeRuleTraffic,
} from '@/features/servers/firewall/api.ts'
import { cn } from '@/utils'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import {
    IconArrowDown,
    IconArrowUp,
    IconDots,
    IconGripVertical,
} from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { Switch } from '@/components/ui/Switch'
import { TableCell, TableRow } from '@/components/ui/Table'

const actionVariant = {
    ACCEPT: 'default',
    DROP: 'destructive',
    REJECT: 'secondary',
} as const

const actionLabel = {
    ACCEPT: 'Accept',
    DROP: 'Drop',
    REJECT: 'Reject',
} as const

interface Props {
    rule: FirewallRule
    onEdit: () => void
    onDelete: () => void
    onToggle: (enabled: boolean) => void
    isMutating: boolean
}

const SortableRuleRow = ({
    rule,
    onEdit,
    onDelete,
    onToggle,
    isMutating,
}: Props) => {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
        useSortable({ id: rule.position! })

    const traffic = describeRuleTraffic(rule)

    return (
        <TableRow
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                isDragging && 'relative z-10 opacity-50',
                !rule.isEnabled && 'text-muted-foreground'
            )}
        >
            {/* The drag handle carries the listeners, not the row: putting them
                on the row makes the toggle and the menu undraggable-but-also
                unclickable, because every pointerdown starts a drag. */}
            <TableCell className={'w-9'}>
                <button
                    type={'button'}
                    className={
                        'cursor-grab text-muted-foreground/50 hover:text-muted-foreground focus-visible:outline-2 focus-visible:outline-ring'
                    }
                    aria-label={`Reorder rule ${(rule.position ?? 0) + 1}`}
                    {...attributes}
                    {...listeners}
                >
                    <IconGripVertical className={'size-4'} />
                </button>
            </TableCell>

            <TableCell className={'w-8 font-mono text-xs text-muted-foreground tabular-nums'}>
                {(rule.position ?? 0) + 1}
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

            <TableCell>
                <span
                    className={
                        'inline-flex items-center gap-1 text-xs text-muted-foreground'
                    }
                >
                    {rule.direction === 'in' ? (
                        <IconArrowDown className={'size-3'} />
                    ) : (
                        <IconArrowUp className={'size-3'} />
                    )}
                    {rule.direction === 'in' ? 'In' : 'Out'}
                </span>
            </TableCell>

            <TableCell>
                <Badge variant={actionVariant[rule.action]}>
                    {actionLabel[rule.action]}
                </Badge>
            </TableCell>

            <TableCell className={'font-mono text-xs'}>
                {traffic ?? (
                    <span className={'font-sans text-muted-foreground'}>
                        Any
                    </span>
                )}
            </TableCell>

            <TableCell className={'font-mono text-xs'}>
                {rule.sourceAddress ?? (
                    <span className={'font-sans text-muted-foreground'}>
                        Any
                    </span>
                )}
            </TableCell>

            <TableCell className={'font-mono text-xs'}>
                {rule.destinationAddress ?? (
                    <span className={'font-sans text-muted-foreground'}>
                        Any
                    </span>
                )}
            </TableCell>

            <TableCell
                className={'max-w-[16rem] truncate text-xs text-muted-foreground'}
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
                            aria-label={`Actions for rule ${(rule.position ?? 0) + 1}`}
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

export default SortableRuleRow
