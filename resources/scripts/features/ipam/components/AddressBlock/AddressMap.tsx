import {
    AddressMap as AddressMapPayload,
    AddressMapState,
    AddressMapUnit,
} from '@/types/address-map.ts'
import { cn } from '@/utils'
import { useMemo, useState } from 'react'

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
    system: 'bg-address-system',
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
}

/**
 * The block's address space as one cell per unit.
 *
 * A table can tell you what an address is; it cannot show you that .88–.90 came free in the middle
 * of an otherwise full /24, which is the question a subnet is usually opened with. Cells are
 * placed by unit index rather than row order, so a hole reads as a hole.
 *
 * Clicking selects, shift-clicking takes the run between the last click and this one — the gesture
 * that turns "reserve .2 through .10 for infrastructure" into one action instead of nine trips
 * through a row menu. Only materialised units can be selected: there is nothing to act on until an
 * address row exists.
 */
const AddressMap = ({ map, selectedIds, onSelectedIdsChange }: Props) => {
    const [filter, setFilter] = useState<AddressMapState | null>(null)
    const [anchor, setAnchor] = useState<number | null>(null)

    const selected = useMemo(() => new Set(selectedIds), [selectedIds])

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

    const select = (unit: AddressMapUnit, extend: boolean) => {
        if (unit.addressId === null) return

        // A shift-click without a prior click has no run to take, so it behaves as a plain click.
        if (extend && anchor !== null) {
            const [from, to] =
                anchor <= unit.index
                    ? [anchor, unit.index]
                    : [unit.index, anchor]

            const run = map.units
                .slice(from, to + 1)
                .map(candidate => candidate.addressId)
                .filter((id): id is number => id !== null)

            onSelectedIdsChange([...new Set([...selectedIds, ...run])])

            return
        }

        setAnchor(unit.index)
        onSelectedIdsChange(
            selected.has(unit.addressId)
                ? selectedIds.filter(id => id !== unit.addressId)
                : [...selectedIds, unit.addressId]
        )
    }

    return (
        <div className={'flex flex-col gap-3'}>
            <div className={'flex flex-wrap items-center gap-2'}>
                {FILTERS.map(state => (
                    <button
                        key={state}
                        type={'button'}
                        aria-pressed={filter === state}
                        onClick={() =>
                            setFilter(filter === state ? null : state)
                        }
                        className={cn(
                            'inline-flex h-7 items-center gap-2 rounded-md border border-dashed px-2.5 text-[0.8rem] font-medium transition-colors',
                            'focus-visible:ring-ring/50 focus-visible:ring-3 focus-visible:outline-none',
                            filter === state
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
                        <span className={'text-muted-foreground tabular-nums'}>
                            {(counts[state] ?? 0).toLocaleString()}
                        </span>
                    </button>
                ))}
                {filter && (
                    <Button
                        variant={'ghost'}
                        size={'sm'}
                        onClick={() => setFilter(null)}
                    >
                        Reset
                    </Button>
                )}
            </div>

            {/* Tall maps scroll in their own box so the page body never grows past the card, and
                narrow viewports scroll sideways rather than squashing the grid out of square. */}
            <div className={'max-h-[28rem] overflow-auto'}>
                <div className={'flex w-fit flex-col gap-[3px]'}>
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
                                        filter !== null && unit.state !== filter

                                    return (
                                        <button
                                            key={unit.index}
                                            type={'button'}
                                            title={describe(unit)}
                                            aria-label={describe(unit)}
                                            aria-pressed={isSelected}
                                            disabled={unit.addressId === null}
                                            onClick={event =>
                                                select(unit, event.shiftKey)
                                            }
                                            className={cn(
                                                'aspect-square rounded-xs transition-opacity',
                                                'focus-visible:ring-ring/50 focus-visible:ring-2 focus-visible:outline-none',
                                                CELL_TONES[unit.state],
                                                dimmed && 'opacity-20',
                                                /* Outside the cell, not inset: at 14px an inset
                                                   ring eats the fill and a selected amber cell
                                                   stops reading as amber. */
                                                isSelected &&
                                                    'ring-foreground z-10 ring-2 ring-offset-1',
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
                Click an address to select it, shift-click to take the run
                between. Selected addresses can be reserved, released or deleted
                together.
            </p>
        </div>
    )
}

export default AddressMap
