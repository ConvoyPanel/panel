import { versionLabels } from '@/features/servers/networking/address-labels.ts'
import { Address, AddressVersion } from '@/types/address.ts'
import { IconWifiOff } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import { CardTable, CardTableColumn } from '@/components/ui/CardTable'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

/**
 * The overview's read of the addresses the Networking tab tabulates.
 *
 * `AddressList` is a table because that tab's subject *is* the addresses: it
 * filters, groups and scrolls them under a sticky header. Here they are one
 * card under five tiles, so this is `CardTable` — the same columns without the
 * frame, and with the tab one click away in the card's header.
 *
 * What this file decides is only which columns exist:
 *
 *  - A fact every address shares is said once, under the list, and its column
 *    disappears — per fact, not per card. Servers are routinely handed a run of
 *    addresses off one block, so the honest reading of that is a card of
 *    addresses over a line naming the gateway they all use. A server holding
 *    addresses from four blocks hoists nothing, and every column is back.
 *  - Each fact keeps its own cell. Joining them into `via 203.0.113.1 ·
 *    bc:24:11:6f:2a:d9` looks like a column and isn't: every MAC then starts
 *    wherever its gateway happened to end.
 *
 * The list is cut rather than scrolled, since the tab holds the rest.
 */

/** Rows the overview shows before deferring to the Networking tab. */
const ROW_LIMIT = 5

const unique = <T,>(values: T[]): T[] => [...new Set(values)]

/** A value the API can leave unset. */
const Value = ({ label, value }: { label: string; value: string | null }) =>
    value ? (
        <CopyValue label={label} value={value} />
    ) : (
        <span aria-label={'None'}>&mdash;</span>
    )

interface Props {
    addresses: Address[]
}

const AddressRows = ({ addresses }: Props) => {
    const shown = addresses.slice(0, ROW_LIMIT)
    const hidden = addresses.length - shown.length

    // A qualifier earns a column only when it distinguishes one row from the
    // next. One value across the whole list is one fact, stated once below —
    // including the fact that there is no value.
    const gateways = unique(addresses.map(a => a.gateway))
    const macs = unique(addresses.map(a => a.macAddress))
    const sharedGateway = gateways.length === 1 ? gateways[0] : null
    const sharedMac = macs.length === 1 ? macs[0] : null

    // The chip yields to real data: an address announces its own version, so it
    // is worth a column only when nothing else is competing for the width.
    const qualified = gateways.length > 1 || macs.length > 1
    const showVersion =
        addresses.length > 0 &&
        !qualified &&
        (unique(addresses.map(a => a.version)).length > 1 ||
            addresses[0].version === AddressVersion.IPv6)

    const columns: CardTableColumn<Address>[] = [
        {
            key: 'address',
            header: 'Address',
            className: 'whitespace-nowrap',
            cell: address => (
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
            ),
        },
        ...(showVersion
            ? [
                  {
                      key: 'version',
                      header: 'Type',
                      cell: (address: Address) => (
                          <Badge variant={'secondary'}>
                              {versionLabels[address.version]}
                          </Badge>
                      ),
                  },
              ]
            : []),
        ...(gateways.length > 1
            ? [
                  {
                      key: 'gateway',
                      header: 'Gateway',
                      className:
                          'text-muted-foreground text-xs whitespace-nowrap',
                      cell: (address: Address) => (
                          <Value label={'Gateway'} value={address.gateway} />
                      ),
                  },
              ]
            : []),
        ...(macs.length > 1
            ? [
                  {
                      key: 'mac',
                      header: 'MAC address',
                      className:
                          'text-muted-foreground text-xs whitespace-nowrap',
                      cell: (address: Address) => (
                          <Value
                              label={'MAC address'}
                              value={address.macAddress}
                          />
                      ),
                  },
              ]
            : []),
    ]

    const shared = [
        sharedGateway && { label: 'Gateway', value: sharedGateway },
        sharedMac && { label: 'MAC address', value: sharedMac },
    ].filter(Boolean) as { label: string; value: string }[]

    const footer =
        shared.length > 0 || hidden > 0 ? (
            <>
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
            </>
        ) : undefined

    return (
        <CardTable
            caption={'IP addresses allocated to this server'}
            rows={shown}
            rowKey={address => address.id}
            columns={columns}
            footer={footer}
            empty={
                <SimpleEmptyState
                    icon={IconWifiOff}
                    title={'No IP addresses'}
                    description={
                        'This server does not have any IP addresses assigned to it.'
                    }
                />
            }
        />
    )
}

export default AddressRows
