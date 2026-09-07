import {
    VersionFilter,
    versionLabels,
} from '@/features/servers/networking/address-labels.ts'
import { Address } from '@/types/address.ts'
import { IconWifiOff } from '@tabler/icons-react'
import { useMemo } from 'react'

import { Badge } from '@/components/ui/Badge'
import { CardTable, CardTableColumn } from '@/components/ui/CardTable'
import CopyValue from '@/components/ui/CopyValue.tsx'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

/**
 * Every address on the server, on the tab whose subject they are.
 *
 * The list used to be a stack of boxed sub-panels, then a bordered table with a
 * tinted header inside a card that was already a box. It is a `CardTable` now:
 * the card is the frame, the rows are its own, and the sticky five-row cap that
 * keeps the nameservers form beside it from being pushed down the page belongs
 * to the component rather than to this file.
 *
 * Three rules keep the rows short, and they are the same ones the overview card
 * (`AddressRows`) applies — the two differ in what they hold, not in how a row
 * is written:
 *
 *  - A fact every address shares is said once, under the list, and its column
 *    disappears: a server handed a run of addresses off one block would
 *    otherwise repeat one gateway and one MAC down its whole height.
 *  - What varies keeps its own cell. Grouping the rows under MAC headings used
 *    to do that job; hoisting does it without spending a row on each group.
 *  - The columns are decided from the whole collection, not the filtered view,
 *    so clicking IPv6 only removes rows. A filter that also restructured the
 *    table would make the thing you are reading move under you.
 */

const unique = <T,>(values: T[]): T[] => [...new Set(values)]

/** A value the API can leave unset. */
const Value = ({ label, value }: { label: string; value: string | null }) =>
    value ? (
        <CopyValue label={label} value={value} />
    ) : (
        <span aria-label={'None'}>&mdash;</span>
    )

interface Props {
    /** The whole collection: it decides the columns. */
    addresses: Address[]
    filter?: VersionFilter
}

const AddressList = ({ addresses, filter = 'all' }: Props) => {
    const visible = useMemo(
        () =>
            filter === 'all'
                ? addresses
                : addresses.filter(a => a.version === filter),
        [addresses, filter]
    )

    const versions = unique(addresses.map(a => a.version))
    const gateways = unique(addresses.map(a => a.gateway))
    const macs = unique(addresses.map(a => a.macAddress))

    const sharedGateway = gateways.length === 1 ? gateways[0] : null
    const sharedMac = macs.length === 1 ? macs[0] : null

    // The chip yields to real data. An address announces its own version — no
    // one reads `2001:db8::5/64` and wonders — so the chip is worth a column
    // only when nothing else is competing for the width, and never once a
    // filter has narrowed the list to one version anyway.
    const qualified = gateways.length > 1 || macs.length > 1
    const showVersion = versions.length > 1 && filter === 'all' && !qualified

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

    // "Shared" includes sharing the absence of a value: one null MAC across
    // every row is still one fact, and saying so once beats a column of dashes.
    const shared = [
        gateways.length === 1 && {
            label: 'Gateway',
            value: sharedGateway,
            missing: 'No gateway reported',
        },
        macs.length === 1 && {
            label: 'MAC address',
            value: sharedMac,
            missing: 'No MAC address reported',
        },
    ].filter(Boolean) as {
        label: string
        value: string | null
        missing: string
    }[]

    return (
        <CardTable
            caption={'IP addresses allocated to this server'}
            rows={visible}
            rowKey={address => address.id}
            columns={columns}
            maxHeight={'max-h-72'}
            empty={
                <SimpleEmptyState
                    icon={IconWifiOff}
                    title={'No IP addresses'}
                    description={
                        'This server does not have any IP addresses assigned to it.'
                    }
                />
            }
            footer={
                shared.length > 0 ? (
                    <dl className={'flex flex-wrap items-center gap-x-6'}>
                        {shared.map(fact =>
                            fact.value ? (
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
                            ) : (
                                <p key={fact.label}>{fact.missing}</p>
                            )
                        )}
                    </dl>
                ) : undefined
            }
        />
    )
}

export default AddressList
