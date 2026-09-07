import {
    AddressMap as AddressMapPayload,
    AddressMapState,
    AddressMapUnit,
} from '@/types/address-map.ts'
import { cn } from '@/utils'
import { useEffect, useMemo, useRef, useState } from 'react'

import { Button } from '@/components/ui/Button'

/** Cells per row. 32 divides every v4 block into whole rows and keeps a /24 to eight lines. */
const COLUMNS = 32

/*
 * Cells are a fixed size rather than a share of the card's width. Stretched to fill, a /24 draws
 * 40px tiles — at that size the eye reads them one at a time, which is the thing the list already
 * does. The map earns its place by being small enough to take in at a glance.
 */
const CELL_SIZE = '0.875rem'

const CELL_TONES: Record<AddressMapState, string> = {
    assigned: 'bg-address-assigned',
    reserved: 'bg-address-reserved',
    /* Hatched, not just neutral: network, broadcast and gateway are structural. The texture says
       "not a colour you can act on" before the reader has hovered anything. */
    system: 'bg-address-system [background-image:repeating-linear-gradient(45deg,color-mix(in_oklab,var(--foreground)_22%,transparent)_0_2px,transparent_2px_4px)]',
    available: 'bg-background ring-muted-foreground/30 ring-1 ring-inset',
    /* Fainter than free, because the difference matters: free is an address the allocator can
       hand out now, ungenerated is one that does not exist yet. */
    ungenerated: 'bg-muted',
}

const STATE_LABELS: Record<AddressMapState, string> = {
    assigned: 'Assigned',
    reserved: 'Reserved',
    system: 'System reserved',
    available: 'Free',
    ungenerated: 'Not generated',
}

/** The states the server can filter on — the same four tokens `filter[state]` accepts. */
const FILTERS: AddressMapState[] = [
    'assigned',
    'reserved',
    'system',
    'available',
]

const describe = (unit: AddressMapUnit) =>
    [unit.ip ?? `Unit ${unit.index}`, STATE_LABELS[unit.state], unit.serverName]
        .filter(Boolean)
        .join(' — ')

interface Props {
    map: AddressMapPayload
    selectedIds: number[]
    onSelectedIdsChange: (ids: number[]) => void
    /**
     * The state filter, shared with the list's faceted filter so the two views are one filter and
     * two renderings rather than two filters that quietly disagree.
     */
    stateFilter: string[]
    onStateFilterChange: (states: string[]) => void
}

/**
 * The block's address space as one cell per unit.
 *
 * A table can tell you what an address is; it cannot show you that .88–.90 came free in the middle
 * of an otherwise full /24, which is the question a subnet is usually opened with. Cells are
 * placed by unit index rather than row order, so a hole reads as a hole.
 *
 * Selection is what makes it more than a picture. Click takes one address, drag takes the run
 * under the pointer, shift-click takes the run from the last click — the gesture that turns
 * "reserve .2 through .10 for infrastructure" into one action instead of nine trips through a row
 * menu. Only materialised units can be selected: there is nothing to act on until an address row
 * exists.
 */
const AddressMap = ({
    map,
    selectedIds,
    onSelectedIdsChange,
    stateFilter,
    onStateFilterChange,
}: Props) => {
    const [anchor, setAnchor] = useState<number | null>(null)
    const [drag, setDrag] = useState<{ from: number; to: number } | null>(null)

    /*
     * A drag ends wherever the pointer is released, including outside the grid, so the release is
     * watched on the window. The ref carries the live range into that listener, so a pointer move
     * does not have to re-subscribe it.
     */
    const dragRef = useRef<{ from: number; to: number } | null>(null)
    const draggedRef = useRef(false)

    const selected = useMemo(() => new Set(selectedIds), [selectedIds])
    const filtering = stateFilter.length > 0

    const counts = useMemo(() => {
        const tally: Record<string, number> = {}

        for (const unit of map.units) {
            tally[unit.state] = (tally[unit.state] ?? 0) + 1
        }

        return tally
    }, [map.units])

    const rows = useMemo(() => {
        const chunks: AddressMapUnit[][] = []

        for (let i = 0; i < map.units.length; i += COLUMNS) {
            chunks.push(map.units.slice(i, i + COLUMNS))
        }

        return chunks
    }, [map.units])

    /** The address ids in an inclusive index range, skipping units with no row behind them. */
    const idsBetween = (from: number, to: number) => {
        const [start, end] = from <= to ? [from, to] : [to, from]

        return map.units
            .slice(start, end + 1)
            .map(unit => unit.addressId)
            .filter((id): id is number => id !== null)
    }

    useEffect(() => {
        const finish = () => {
            const range = dragRef.current

            dragRef.current = null
            setDrag(null)

            // A press that never left its cell is a click; the click handler owns that case.
            if (!range || range.from === range.to) return

            draggedRef.current = true
            onSelectedIdsChange([
                ...new Set([
                    ...selectedIds,
                    ...idsBetween(range.from, range.to),
                ]),
            ])
        }

        window.addEventListener('pointerup', finish)
        window.addEventListener('pointercancel', finish)

        return () => {
            window.removeEventListener('pointerup', finish)
            window.removeEventListener('pointercancel', finish)
        }
        // `finish` unions the drag with the selection it started from, so it has to see the
        // current one; the units are what it resolves indices against.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedIds, map.units])

    const toggle = (unit: AddressMapUnit, extend: boolean) => {
        if (unit.addressId === null) return

        // A shift-click without a prior click has no run to take, so it behaves as a plain click.
        if (extend && anchor !== null) {
            onSelectedIdsChange([
                ...new Set([...selectedIds, ...idsBetween(anchor, unit.index)]),
            ])

            return
        }

        setAnchor(unit.index)
        onSelectedIdsChange(
            selected.has(unit.addressId)
                ? selectedIds.filter(id => id !== unit.addressId)
                : [...selectedIds, unit.addressId]
        )
    }

    const toggleFilter = (state: AddressMapState) =>
        onStateFilterChange(
            stateFilter.includes(state)
                ? stateFilter.filter(entry => entry !== state)
                : [...stateFilter, state]
        )

    const inDrag = (index: number) =>
        drag !== null &&
        index >= Math.min(drag.from, drag.to) &&
        index <= Math.max(drag.from, drag.to)

    return (
        <div className={'flex flex-col gap-3'}>
            <div className={'flex flex-wrap items-center gap-2'}>
                {FILTERS.map(state => {
                    const active = stateFilter.includes(state)

                    return (
                        <button
                            key={state}
                            type={'button'}
                            aria-pressed={active}
                            onClick={() => toggleFilter(state)}
                            className={cn(
                                'inline-flex h-7 items-center gap-2 rounded-md border border-dashed px-2.5 text-[0.8rem] font-medium transition-colors',
                                'focus-visible:ring-ring/50 focus-visible:ring-3 focus-visible:outline-none',
                                active
                                    ? 'border-primary bg-primary/5 border-solid'
                                    : 'hover:bg-accent'
                            )}
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'size-2 rounded-xs',
                                    CELL_TONES[state]
                                )}
                            />
                            {STATE_LABELS[state]}
                            <span
                                className={'text-muted-foreground tabular-nums'}
                            >
                                {(counts[state] ?? 0).toLocaleString()}
                            </span>
                        </button>
                    )
                })}
                {filtering && (
                    <Button
                        variant={'ghost'}
                        size={'sm'}
                        onClick={() => onStateFilterChange([])}
                    >
                        Reset
                    </Button>
                )}
            </div>

            {/* Tall maps scroll in their own box so the page body never grows past the card, and
                narrow viewports scroll sideways rather than squashing the grid out of square.
                `clip-slack` pads the clip box so a selected cell's ring — which sits outside the
                cell — is not shaved off at the container's edge; see docs/card-design.md. */}
            <div className={'clip-slack max-h-[28rem] overflow-auto'}>
                <div
                    className={'flex w-fit flex-col gap-[3px]'}
                    /* Dragging across cells would otherwise select the row labels as text. */
                    style={{ userSelect: drag ? 'none' : undefined }}
                >
                    {rows.map((row, rowIndex) => (
                        <div
                            key={rowIndex}
                            className={'flex items-center gap-2'}
                        >
                            <span
                                className={
                                    'text-muted-foreground w-24 shrink-0 truncate font-mono text-[0.6875rem]'
                                }
                            >
                                {row[0]?.ip ?? `#${row[0]?.index ?? 0}`}
                            </span>
                            <div
                                className={'grid gap-[3px]'}
                                style={{
                                    gridTemplateColumns: `repeat(${COLUMNS}, ${CELL_SIZE})`,
                                }}
                            >
                                {row.map(unit => {
                                    const isSelected =
                                        unit.addressId !== null &&
                                        selected.has(unit.addressId)
                                    const dimmed =
                                        filtering &&
                                        !stateFilter.includes(unit.state)
                                    const previewing = inDrag(unit.index)

                                    return (
                                        <button
                                            key={unit.index}
                                            type={'button'}
                                            title={describe(unit)}
                                            aria-label={describe(unit)}
                                            aria-pressed={isSelected}
                                            disabled={unit.addressId === null}
                                            onPointerDown={() => {
                                                if (unit.addressId === null)
                                                    return

                                                draggedRef.current = false
                                                dragRef.current = {
                                                    from: unit.index,
                                                    to: unit.index,
                                                }
                                                setDrag(dragRef.current)
                                            }}
                                            onPointerEnter={() => {
                                                if (!dragRef.current) return

                                                dragRef.current = {
                                                    ...dragRef.current,
                                                    to: unit.index,
                                                }
                                                setDrag(dragRef.current)
                                            }}
                                            onClick={event => {
                                                // The drag already committed this run; the click
                                                // that follows the release must not undo a cell.
                                                if (draggedRef.current) {
                                                    draggedRef.current = false

                                                    return
                                                }

                                                toggle(unit, event.shiftKey)
                                            }}
                                            className={cn(
                                                'aspect-square rounded-xs transition-opacity',
                                                'focus-visible:ring-ring/50 focus-visible:ring-2 focus-visible:outline-none',
                                                CELL_TONES[unit.state],
                                                dimmed && 'opacity-20',
                                                /* Outside the cell, not inset: at 14px an inset
                                                   ring eats the fill and a selected amber cell
                                                   stops reading as amber. */
                                                (isSelected || previewing) &&
                                                    'ring-foreground z-10 ring-2 ring-offset-1',
                                                previewing && 'opacity-80',
                                                unit.addressId === null
                                                    ? 'cursor-default'
                                                    : 'hover:opacity-70'
                                            )}
                                        />
                                    )
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <p className={'text-muted-foreground text-xs'}>
                Click an address to select it, drag across a run, or shift-click
                to extend from the last one. Selected addresses can be reserved,
                released or deleted together.
            </p>
        </div>
    )
}

export default AddressMap
