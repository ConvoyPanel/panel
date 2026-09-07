import { versionLabels } from '@/features/servers/networking/address-labels.ts'
import { Address, AddressVersion } from '@/types/address.ts'
import { IconWifiOff } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

/**
 * The overview's read of the addresses the Networking tab tabulates.
 *
 * `AddressList` is a table because the Networking tab's subject *is* the
 * addresses. On the overview they are one card under five tiles, and a table
 * there costs a bordered box and a tinted header strip inside a card that is
 * already a box — three surfaces, and a header band, to label one line per
 * address. These are the card's own rows instead: they bleed to its edges and
 * hang off its dividers, the way the node network page frames its interfaces.
 *
 * Two rules keep a row to one line:
 *
 *  - The address leads, and whatever distinguishes it from the address under it
 *    trails beside it in the muted mono the rest of the app uses for machine
 *    values — not flung to the card's right edge, which on a 1500px overview
 *    leaves a gateway stranded a thousand pixels from the address it belongs to.
 *  - A fact every address shares is said once, under the list. Servers are
 *    routinely handed a run of addresses off one block, so the honest reading of
 *    that is a card of addresses over a line naming the gateway they all use —
 *    not the same gateway typed out five times, or a column of em dashes where
 *    there is none.
 *
 * The list is cut, not scrolled. `AddressList` scrolls five rows under a sticky
 * header because that page has nowhere else to send you; here the tab holding
 * every address is one click away in the card's header.
 */

/** Rows the overview shows before deferring to the Networking tab. */
const ROW_LIMIT = 5

const unique = <T,>(values: T[]): T[] => [...new Set(values)]

interface Props {
    addresses: Address[]
}

const AddressRows = ({ addresses }: Props) => {
    if (addresses.length === 0) {
        return (
            <div className={'px-4 pb-4'}>
                <SimpleEmptyState
                    icon={IconWifiOff}
                    title={'No IP addresses'}
                    description={
                        'This server does not have any IP addresses assigned to it.'
                    }
                />
            </div>
        )
    }

    const shown = addresses.slice(0, ROW_LIMIT)
    const hidden = addresses.length - shown.length

    // A qualifier earns room on the row only when it distinguishes that row
    // from the one under it. One value across the whole list is one fact, and
    // it is stated once below — including the fact that there is no value.
    const gateways = unique(addresses.map(a => a.gateway))
    const macs = unique(addresses.map(a => a.macAddress))
    const sharedGateway = gateways.length === 1 ? gateways[0] : null
    const sharedMac = macs.length === 1 ? macs[0] : null
    const showGatewayPerRow = gateways.length > 1
    const showMacPerRow = macs.length > 1

    const shared = [
        sharedGateway && { label: 'Gateway', value: sharedGateway },
        sharedMac && { label: 'MAC address', value: sharedMac },
    ].filter(Boolean) as { label: string; value: string }[]

    // The version is on the row only when the stack is mixed. A card of seven
    // IPv4 addresses says so once, in the header, and seven blue chips down the
    // left margin say it six more times.
    const showVersion =
        unique(addresses.map(a => a.version)).length > 1 ||
        addresses[0].version === AddressVersion.IPv6

    return (
        <>
            <ul className={'divide-y border-t'}>
                {shown.map(address => {
                    const trailing = [
                        showGatewayPerRow && address.gateway
                            ? `via ${address.gateway}`
                            : null,
                        showMacPerRow ? address.macAddress : null,
                    ].filter(Boolean)

                    return (
                        <li
                            key={address.id}
                            className={
                                'flex flex-wrap items-baseline gap-x-6 gap-y-0.5 px-4 py-3'
                            }
                        >
                            {/* A floor rather than a width: the qualifiers line
                                up in a column past the longest ordinary
                                address, and one long IPv6 pushes its own row
                                out instead of setting the column for every
                                row above it. */}
                            <span
                                className={
                                    'flex min-w-0 basis-48 items-baseline gap-2'
                                }
                            >
                                <CopyValue
                                    label={'Address'}
                                    value={`${address.ip}/${address.prefixLength}`}
                                    className={
                                        'text-foreground font-medium tracking-tight tabular-nums'
                                    }
                                >
                                    {address.ip}
                                    <span
                                        className={
                                            'text-muted-foreground font-normal'
                                        }
                                    >
                                        /{address.prefixLength}
                                    </span>
                                </CopyValue>
                                {showVersion && (
                                    <Badge variant={'secondary'}>
                                        {versionLabels[address.version]}
                                    </Badge>
                                )}
                            </span>
                            {trailing.length > 0 && (
                                <span
                                    className={
                                        'text-muted-foreground truncate font-mono text-xs'
                                    }
                                >
                                    {trailing.join(' · ')}
                                </span>
                            )}
                        </li>
                    )
                })}
            </ul>

            {(shared.length > 0 || hidden > 0) && (
                <div
                    className={
                        'text-muted-foreground flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-t px-4 py-3 text-xs'
                    }
                >
                    <dl className={'flex flex-wrap items-center gap-x-6'}>
                        {shared.map(fact => (
                            <div
                                key={fact.label}
                                className={'flex items-center gap-1.5'}
                            >
                                <dt>{fact.label}</dt>
                                <dd>
                                    <CopyValue
                                        label={fact.label}
                                        value={fact.value}
                                        className={'text-foreground'}
                                    />
                                </dd>
                            </div>
                        ))}
                    </dl>
                    {hidden > 0 && (
                        <p className={'tabular-nums'}>
                            {shown.length} of {addresses.length}
                        </p>
                    )}
                </div>
            )}
        </>
    )
}

export default AddressRows
