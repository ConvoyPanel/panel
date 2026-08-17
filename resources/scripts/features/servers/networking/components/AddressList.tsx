import { Address, AddressVersion } from '@/types/address.ts'
import { cn } from '@/utils'
import { IconWifiOff } from '@tabler/icons-react'
import { useMemo, useState } from 'react'

import { Badge } from '@/components/ui/Badge'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/ToggleGroup'

/**
 * One line per address, in a table whose height stops growing at five rows.
 *
 * The list used to be a stack of boxed sub-panels, 115px each, and the card's
 * height was a function of the address count — so every extra IP pushed the DNS
 * form (the only editable thing on the Networking tab) further down the page.
 * Three rules keep the height bounded and the rows short:
 *
 *  - The body caps at `max-h-72` and scrolls under a sticky header.
 *  - A repeated value is said once. The MAC comes from the address block, not
 *    the address, so a long list repeats one 17-character string down its whole
 *    height: two or more distinct MACs become row groups, a single one becomes
 *    a line under the table.
 *  - Nothing is hidden behind a sheet. On the page whose subject *is* the
 *    addresses, scrolling five visible rows beats "Show all 8 →".
 *
 * The second rule only pays for itself on a list long enough to repeat, which
 * is what `HOISTABLE_ROWS` marks. Below it every fact stays a column: hoisting
 * a one-address table leaves a single column with a summary above it and a
 * footnote below, which scatters one short record across three strips.
 */

/** Rows a repeated value must span before it is worth saying once instead. */
const HOISTABLE_ROWS = 3

const versionLabels: Record<AddressVersion, string> = {
    [AddressVersion.IPv4]: 'IPv4',
    [AddressVersion.IPv6]: 'IPv6',
}

type VersionFilter = AddressVersion | 'all'

const unique = <T,>(values: T[]): T[] => [...new Set(values)]

/** A cell whose value the API can leave unset. */
const ValueCell = ({
    label,
    value,
    className,
}: {
    label: string
    value: string | null
    className?: string
}) => (
    <TableCell
        className={cn(
            'text-muted-foreground hidden px-3 py-2 whitespace-nowrap @2xl:table-cell',
            className
        )}
    >
        {value ? (
            <CopyValue label={label} value={value} />
        ) : (
            <span aria-label={'None'}>&mdash;</span>
        )}
    </TableCell>
)

const AddressCell = ({
    address,
    /* Whatever the narrow card drops from the row, folded back under the
       address: the container query measures AppLayout's content area, not the
       card, so these columns go before the card itself is anywhere near
       narrow. */
    fold,
}: {
    address: Address
    fold: string[]
}) => (
    <TableCell className={'px-3 py-2 whitespace-nowrap'}>
        <CopyValue
            label={'Address'}
            value={`${address.ip}/${address.prefixLength}`}
            className={
                'text-foreground font-medium tracking-tight tabular-nums'
            }
        >
            {address.ip}
            <span className={'text-muted-foreground font-normal'}>
                /{address.prefixLength}
            </span>
        </CopyValue>
        {fold.length > 0 && (
            <span
                className={
                    'text-muted-foreground block font-mono text-xs @2xl:hidden'
                }
            >
                {fold.join(' · ')}
            </span>
        )}
    </TableCell>
)

interface Props {
    addresses: Address[]
}

const AddressList = ({ addresses }: Props) => {
    const [filter, setFilter] = useState<VersionFilter>('all')

    const counts = useMemo(
        () => ({
            [AddressVersion.IPv4]: addresses.filter(
                a => a.version === AddressVersion.IPv4
            ).length,
            [AddressVersion.IPv6]: addresses.filter(
                a => a.version === AddressVersion.IPv6
            ).length,
        }),
        [addresses]
    )

    const isMixed =
        counts[AddressVersion.IPv4] > 0 && counts[AddressVersion.IPv6] > 0

    const visible = useMemo(
        () =>
            filter === 'all'
                ? addresses
                : addresses.filter(a => a.version === filter),
        [addresses, filter]
    )

    // Short list: every fact stays a column, and the table is the whole card.
    //
    // Decided from the whole collection, not the filtered view, so that
    // clicking IPv6 only removes rows — a filter that also restructured the
    // table's columns would make the thing you're reading move under you.
    const isShort = addresses.length < HOISTABLE_ROWS
    const macs = unique(addresses.map(a => a.macAddress))
    const isGrouped = !isShort && macs.length > 1
    // "Shared" includes sharing the absence of a value: one null MAC across
    // every row is still one fact, and saying so once beats a column of dashes.
    const sharedMac = !isShort && macs.length === 1 ? macs[0] : null
    const hasSharedMac = !isShort && macs.length === 1

    const groups = useMemo(() => {
        if (!isGrouped) return [{ key: '', mac: null, rows: visible }]

        const byMac = new Map<string, { mac: string | null; rows: Address[] }>()

        for (const address of visible) {
            const key = address.macAddress ?? ''
            const group = byMac.get(key) ?? {
                mac: address.macAddress,
                rows: [],
            }

            group.rows.push(address)
            byMac.set(key, group)
        }

        return [...byMac.entries()].map(([key, group]) => ({ key, ...group }))
    }, [visible, isGrouped])

    // The version is worth a column when it varies, or when the list is too
    // short for the count line above it to be carrying that fact instead.
    const showVersion = isShort || (isMixed && filter === 'all')
    const showMac = isShort
    const showSummary = !isShort || isMixed
    const columnCount = 2 + Number(showVersion) + Number(showMac)

    if (addresses.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconWifiOff}
                title={'No IP addresses'}
                description={
                    'This server does not have any IP addresses assigned to it.'
                }
            />
        )
    }

    const summary = isMixed
        ? `${addresses.length} addresses · ${counts[AddressVersion.IPv4]} IPv4 · ${counts[AddressVersion.IPv6]} IPv6`
        : `${addresses.length} ${versionLabels[addresses[0].version]} ${
              addresses.length === 1 ? 'address' : 'addresses'
          }`

    return (
        <div className={'flex flex-col gap-3'}>
            {showSummary && (
                <div
                    className={
                        'flex flex-wrap items-center justify-between gap-2'
                    }
                >
                    <p className={'text-muted-foreground text-xs tabular-nums'}>
                        {summary}
                    </p>
                    {isMixed && (
                        <ToggleGroup
                            variant={'outline'}
                            size={'sm'}
                            spacing={0}
                            multiple={false}
                            value={[filter]}
                            onValueChange={value => {
                                // Single-select: ignore the empty array a
                                // second click on the pressed item produces,
                                // so one segment is always active.
                                if (value[0])
                                    setFilter(value[0] as VersionFilter)
                            }}
                            aria-label={'Filter addresses by IP version'}
                        >
                            <ToggleGroupItem value={'all'}>All</ToggleGroupItem>
                            <ToggleGroupItem value={AddressVersion.IPv4}>
                                IPv4
                            </ToggleGroupItem>
                            <ToggleGroupItem value={AddressVersion.IPv6}>
                                IPv6
                            </ToggleGroupItem>
                        </ToggleGroup>
                    )}
                </div>
            )}

            <div className={'overflow-hidden rounded-lg border'}>
                {/* The scroll container has to be the sticky header's own
                    scrolling ancestor, which rules out the `Table` primitive:
                    it wraps the table in its own `overflow-auto` div, and a
                    nested one would capture `position: sticky` and never
                    scroll. Everything below the table element is a primitive.

                    `scroll-fade-b` only — the mask applies to the sticky header
                    too, so a top fade would dissolve the header as you scroll
                    under it. The header's own background does that job. */}
                <div
                    className={
                        'scroll-fade-b max-h-72 overflow-y-auto overscroll-contain'
                    }
                >
                    <table className={'w-full caption-bottom text-sm'}>
                        <caption className={'sr-only'}>
                            IP addresses allocated to this server
                        </caption>
                        <TableHeader
                            className={
                                // A collapsed border under a sticky cell is
                                // dropped while scrolling in Chromium, so the
                                // rule is drawn as a pseudo-element instead.
                                '[&_th]:bg-muted [&_th]:after:bg-border [&_th]:sticky [&_th]:top-0 [&_th]:z-10 [&_th]:h-9 [&_th]:px-3 [&_th]:text-xs [&_th]:after:absolute [&_th]:after:inset-x-0 [&_th]:after:bottom-0 [&_th]:after:h-px'
                            }
                        >
                            {/* `w-px` shrinks a column to its content, and the
                                last one takes `w-full` to swallow the slack —
                                without it an auto table spreads four short
                                values across the card's whole width and the
                                row stops reading as one fact. */}
                            <TableRow className={'hover:bg-transparent'}>
                                <TableHead className={'w-px whitespace-nowrap'}>
                                    Address
                                </TableHead>
                                {showVersion && (
                                    <TableHead
                                        className={'w-px whitespace-nowrap'}
                                    >
                                        Type
                                    </TableHead>
                                )}
                                <TableHead
                                    className={cn(
                                        'hidden w-px whitespace-nowrap @2xl:table-cell',
                                        !showMac && 'w-full'
                                    )}
                                >
                                    Gateway
                                </TableHead>
                                {showMac && (
                                    <TableHead
                                        className={
                                            'hidden w-full whitespace-nowrap @2xl:table-cell'
                                        }
                                    >
                                        MAC address
                                    </TableHead>
                                )}
                            </TableRow>
                        </TableHeader>
                        {groups.map(group => (
                            <TableBody key={group.key}>
                                {isGrouped && (
                                    <TableRow
                                        className={'hover:bg-transparent'}
                                    >
                                        <th
                                            scope={'colgroup'}
                                            colSpan={columnCount}
                                            className={
                                                'bg-muted/50 text-muted-foreground h-8 px-3 text-left text-xs font-medium'
                                            }
                                        >
                                            {/* Labelled, because a bare MAC
                                                sitting on its own row reads as
                                                a stray value rather than as
                                                the interface these addresses
                                                share. */}
                                            {group.mac ? (
                                                <>
                                                    MAC address{' '}
                                                    <CopyValue
                                                        label={'MAC address'}
                                                        value={group.mac}
                                                        className={
                                                            'text-foreground font-normal'
                                                        }
                                                    />
                                                </>
                                            ) : (
                                                'No MAC address reported'
                                            )}
                                        </th>
                                    </TableRow>
                                )}
                                {group.rows.map(address => (
                                    <TableRow key={address.id}>
                                        <AddressCell
                                            address={address}
                                            fold={[
                                                address.gateway
                                                    ? `via ${address.gateway}`
                                                    : '',
                                                showMac && address.macAddress
                                                    ? address.macAddress
                                                    : '',
                                            ].filter(Boolean)}
                                        />
                                        {showVersion && (
                                            <TableCell className={'px-3 py-2'}>
                                                <Badge variant={'secondary'}>
                                                    {
                                                        versionLabels[
                                                            address.version
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                        )}
                                        <ValueCell
                                            label={'Gateway'}
                                            value={address.gateway}
                                        />
                                        {showMac && (
                                            <ValueCell
                                                label={'MAC address'}
                                                value={address.macAddress}
                                            />
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        ))}
                    </table>
                </div>
            </div>

            {/* The one fact a long list would otherwise repeat down its whole
                height. A grouped table already carries it in its headers. */}
            {hasSharedMac && (
                <p className={'text-muted-foreground text-xs'}>
                    {sharedMac ? (
                        <>
                            MAC address{' '}
                            <CopyValue
                                label={'MAC address'}
                                value={sharedMac}
                                className={'text-foreground'}
                            />
                        </>
                    ) : (
                        'No MAC address reported'
                    )}
                </p>
            )}
        </div>
    )
}

export default AddressList
