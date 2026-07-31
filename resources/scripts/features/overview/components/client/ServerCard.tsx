import PowerStateRail from '@/features/servers/components/PowerStateRail.tsx'
import { Route as ServerIndexRoute } from '@/routes/_app/servers.$serverUuid.tsx'
import { IconChevronRight } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    server: App.Data.Server.ServerData
}

const ServerCard = ({ server }: Props) => {
    const memory = byteSize(server.memory, { units: 'iec', precision: 0 })
    const disk = byteSize(server.disk, { units: 'iec', precision: 0 })
    const specs = [
        { label: 'CPU', value: `${server.cpu} vCPU` },
        { label: 'Memory', value: `${memory.value} ${memory.unit}` },
        { label: 'Disk', value: `${disk.value} ${disk.unit}` },
    ]

    return (
        <Item
            size={'sm'}
            /* Each row is its own nova surface: `bg-card` + the flat ring is
               Card's own recipe (docs/card-design.md), applied to a row rather
               than a panel. Deliberately not `variant="muted"` -- that tint is a
               sub-panel meant to sit *inside* a Card (see Item.tsx), and out here
               on AppLayout's `bg-muted/40` it lands a tenth of one tint from its
               own background, which is no edge at all.

               `border-0` and `overflow-hidden` are the rail's contract, not
               decoration: Item's default 1px transparent border would otherwise
               sit between the rail and the ring as a white seam, and clipping is
               what gives the rail the row's own corner. */
            className={
                'relative overflow-hidden border-0 bg-card pl-5 ring-1 ring-foreground/10 transition-[background-color,box-shadow] duration-100 hover:bg-accent/50 hover:ring-foreground/20'
            }
        >
            <PowerStateRail state={server.powerState} />
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'max-w-full'}>
                    {/* The `after` overlay stretches this one link across the
                        whole row, rather than wrapping the row in an anchor --
                        the row is a listitem, and an anchor carrying that role
                        would stop being announced as a link. Nothing else in the
                        row is interactive, so the overlay has nobody to cover:
                        if that ever changes, the new control needs to sit above
                        it rather than the overlay being weakened. */}
                    <Link
                        to={ServerIndexRoute.to}
                        params={{
                            serverUuid: server.uuidShort,
                        }}
                        className={'truncate after:absolute after:inset-0'}
                    >
                        {server.name}
                    </Link>
                    {/* `ready` is the resting state of every healthy server, so
                        badging it says nothing -- a badge here means "this one
                        needs your attention". */}
                    {server.lifecycle !== 'ready' && (
                        <Badge
                            variant={'secondary'}
                            className={'shrink-0 capitalize'}
                        >
                            {server.lifecycle.replace(/_/g, ' ')}
                        </Badge>
                    )}
                    {/* Its own badge rather than a lifecycle value, so a suspended
                        server that is also mid-install shows both facts. */}
                    {server.suspendedAt && (
                        <Badge variant={'destructive'} className={'shrink-0'}>
                            Suspended
                        </Badge>
                    )}
                </ItemTitle>
                <ItemDescription className={'truncate'}>
                    {server.hostname}
                </ItemDescription>
            </ItemContent>
            <dl
                className={
                    'order-last grid w-full grid-cols-3 gap-2 sm:order-none sm:w-auto sm:min-w-72 sm:gap-4'
                }
            >
                {specs.map(spec => (
                    <div key={spec.label}>
                        <dt className={'text-muted-foreground text-xs'}>
                            {spec.label}
                        </dt>
                        <dd className={'text-sm'}>{spec.value}</dd>
                    </div>
                ))}
            </dl>
            {/* Decorative: the whole row is already the link, so this only says
                so. Kept visible rather than hover-only -- a touch device never
                hovers, and the hint is most useful exactly there. */}
            <IconChevronRight
                aria-hidden
                className={
                    'text-muted-foreground ml-auto hidden size-4 shrink-0 opacity-50 transition-opacity duration-100 group-hover/item:opacity-100 sm:block'
                }
            />
        </Item>
    )
}

export default ServerCard
