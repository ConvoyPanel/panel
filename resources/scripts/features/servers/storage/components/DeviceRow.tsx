import {
    type StorageDevice,
    describeBacking,
    deviceKind,
    deviceKindLabels,
    deviceOptions,
    formatBytes,
} from '@/features/servers/storage/api.ts'
import { cn } from '@/utils'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { IconGripVertical } from '@tabler/icons-react'
import type { ReactNode } from 'react'

import { Badge } from '@/components/ui/Badge.tsx'
import { Switch } from '@/components/ui/Switch'
import { TableCell, TableRow } from '@/components/ui/Table'

interface CellProps {
    device: StorageDevice
    /** 1-based position in the boot order, or null when the device is skipped. */
    rank: number | null
    isMutating: boolean
    onToggleBoot: (boots: boolean) => void
    /** The grip, wired for the row in the table and inert for the floating copy. */
    handle: ReactNode
}

const DeviceCells = ({
    device,
    rank,
    isMutating,
    onToggleBoot,
    handle,
}: CellProps) => {
    const kind = deviceKind(device)
    const backing = describeBacking(device)
    const options = deviceOptions(device)

    return (
        <>
            <TableCell className={'w-9 pl-4'}>{handle}</TableCell>

            <TableCell className={'w-8 font-mono tabular-nums'}>
                {/* Blank rather than a dash: the switch beside it already says
                    the device has no place in the order, and a column of
                    dashes reads as a rank of its own. Kept for screen readers,
                    which have no switch in view to infer it from. */}
                {rank ?? <span className={'sr-only'}>Not tried at boot</span>}
            </TableCell>

            <TableCell className={'w-12'}>
                <Switch
                    checked={rank !== null}
                    disabled={isMutating}
                    onCheckedChange={onToggleBoot}
                    aria-label={
                        rank !== null
                            ? `Stop booting from ${device.interface}`
                            : `Boot from ${device.interface}`
                    }
                />
            </TableCell>

            <TableCell className={'w-44'}>
                <span className={'flex items-center gap-2'}>
                    <span className={'font-mono text-sm font-medium'}>
                        {device.interface}
                    </span>
                    <Badge variant={'secondary'} className={'font-normal'}>
                        {deviceKindLabels[kind]}
                    </Badge>
                </span>
            </TableCell>

            <TableCell
                className={'text-muted-foreground max-w-0 truncate font-mono'}
                title={backing ?? undefined}
            >
                {backing ?? <span className={'font-sans italic'}>Empty</span>}
            </TableCell>

            <TableCell className={'w-24 text-right font-mono tabular-nums'}>
                {device.size > 0 ? formatBytes(device.size) : '—'}
            </TableCell>

            {/* One muted, truncating line rather than a row of badges. Four
                chips wrapped onto a second line and made the disk rows taller
                than everything else, and they were claiming emphasis the least
                important column on the row has no business claiming -- while
                squeezing the backing volume, which is how you tell the devices
                apart. */}
            <TableCell
                className={'text-muted-foreground w-56 truncate'}
                title={options.join(' · ') || undefined}
            >
                {options.join(' · ')}
            </TableCell>
        </>
    )
}

const gripClasses =
    'text-muted-foreground/50 hover:text-muted-foreground focus-visible:outline-ring cursor-grab focus-visible:outline-2'

/** Lets the card find the row it is about to lift a copy out of, to measure it. */
export const deviceRowId = (interfaceName: string) =>
    `device-row-${interfaceName}`

type Props = Omit<CellProps, 'handle'>

/**
 * One attached device, and whether the firmware tries it.
 *
 * Boot participation is a switch on the device rather than membership of a
 * second list: a device the server has but does not boot from is still a device
 * the server has, and hiding it behind an "add" menu made removing it from the
 * order look like deleting the disk.
 */
const DeviceRow = ({ device, rank, ...cells }: Props) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: device.interface, disabled: rank === null })

    return (
        <TableRow
            ref={setNodeRef}
            id={deviceRowId(device.interface)}
            // Translate, not Transform: the sortable strategy only ever moves
            // rows, so the scale half of the transform is a no-op that rides
            // along on every row's inline style for the length of the drag.
            style={{ transform: CSS.Translate.toString(transform), transition }}
            className={cn(
                'text-xs',
                // The floating copy is the one being moved, so this is the hole
                // it came out of — kept in place and dimmed so the row it will
                // drop back into stays legible.
                isDragging && 'opacity-40',
                // A device outside the boot order is still real hardware, so it
                // stays legible — just visibly not part of the sequence above.
                rank === null && 'text-muted-foreground'
            )}
        >
            <DeviceCells
                device={device}
                rank={rank}
                {...cells}
                handle={
                    // Only the devices that boot have an order to drag within,
                    // and listeners live on the handle rather than the row so
                    // the switch stays clickable.
                    rank !== null && (
                        <button
                            type={'button'}
                            className={gripClasses}
                            aria-label={`Reorder ${device.interface}`}
                            {...attributes}
                            {...listeners}
                        >
                            <IconGripVertical className={'size-4'} />
                        </button>
                    )
                }
            />
        </TableRow>
    )
}

/**
 * The copy that follows the pointer.
 *
 * It carries no sortable registration -- the row it was lifted out of still
 * holds that -- so it is free to go anywhere on the page while the list below
 * keeps sorting itself.
 */
export const DeviceRowOverlay = ({ device, rank, ...cells }: Props) => (
    <TableRow
        // Chrome lives on the table around it -- a bare `tr` paints a
        // background unevenly and drops a ring outright. Hover is off because
        // the pointer is by definition on top of this one.
        className={'text-xs hover:bg-transparent [&>td]:border-0'}
    >
        <DeviceCells
            device={device}
            rank={rank}
            {...cells}
            handle={
                <span className={cn(gripClasses, 'cursor-grabbing')}>
                    <IconGripVertical className={'size-4'} />
                </span>
            }
        />
    </TableRow>
)

export default DeviceRow
