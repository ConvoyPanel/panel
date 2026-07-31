import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'
import useVlansModalStore from '@/features/nodes/hooks/use-vlans-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { NetworkInterface, Vlan } from '@/types/network-interface.ts'
import { cn } from '@/utils'
import { IconDots, IconPlus } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    interface: NetworkInterface
}

const count = (value: number, noun: string) =>
    `${value} ${noun}${value === 1 ? '' : 's'}`

/**
 * The right-hand summary every row shares. Pools come before servers because
 * they answer the question that decides whether a bridge is usable at all
 * ("can a server get an address here?") — the server count only says whether
 * anyone took it up.
 */
const InterfaceMeta = ({
    networkInterface,
}: {
    networkInterface: NetworkInterface
}) => (
    <div className={'text-muted-foreground shrink-0 text-sm tabular-nums'}>
        {networkInterface.addressPoolsCount > 0 && (
            <>
                {count(networkInterface.addressPoolsCount, 'pool')}
                <span className={'mx-1.5'}>·</span>
            </>
        )}
        {count(networkInterface.serversCount, 'server')}
    </div>
)

const Actions = ({
    label,
    onEdit,
    onDelete,
}: {
    label: string
    onEdit: () => void
    onDelete: () => void
}) => (
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button size={'icon'} variant={'ghost'} aria-label={label}>
                <IconDots className={'text-muted-foreground'} />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align={'end'} className={'w-60'}>
            <DropdownMenuItem onClick={onEdit}>Edit</DropdownMenuItem>
            <DropdownMenuItem variant={'destructive'} onClick={onDelete}>
                Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
)

const VlanRow = ({
    vlan,
    networkInterface,
    isLast,
}: {
    vlan: Vlan
    networkInterface: NetworkInterface
    isLast: boolean
}) => {
    const openVlanModal = useOpenModal(useVlansModalStore)
    const isDefault = networkInterface.vlanTag === vlan.tag
    const isDeclared = vlan.id !== null

    return (
        <div
            className={cn(
                // pr-3 matches the interface row's px-3, not the panel's own
                // indent: the action buttons form a single column down the
                // right edge, so parents and children have to share it.
                'relative flex items-center gap-3 py-1.5 pr-3 pl-4',
                // The stem, drawn as a rule rather than a left border so the
                // last row can stop it halfway — at the elbow — instead of
                // trailing past the final child. `last:` can't express that
                // either: the "Declare VLAN" button is the panel's real last
                // element, so the selector would never match a row.
                'before:bg-border before:absolute before:top-0 before:left-0 before:w-px',
                isLast ? 'before:bottom-1/2' : 'before:bottom-0'
            )}
        >
            {/* The elbow tying this row back to the stem. Vertically centred by
                the flex row, which is the height the last stem stops at. */}
            <span
                className={'border-border -ml-4 w-4 shrink-0 border-b'}
                aria-hidden
            />
            <div className={'flex min-w-0 flex-1 flex-wrap items-center gap-2'}>
                <span className={'font-mono text-sm'}>VLAN {vlan.tag}</span>
                {vlan.name && (
                    <span className={'truncate text-sm'}>{vlan.name}</span>
                )}
                {isDefault && (
                    <Badge variant={'secondary'} className={'w-fit'}>
                        Bridge default
                    </Badge>
                )}
                {!isDeclared && (
                    // Worth marking: it exists only because a server carries
                    // the tag, so it can't be named or removed as it stands.
                    <Badge variant={'outline'} className={'w-fit'}>
                        Undeclared
                    </Badge>
                )}
            </div>
            <div
                className={
                    'text-muted-foreground shrink-0 text-sm tabular-nums'
                }
            >
                {count(vlan.serversCount, 'server')}
            </div>
            {isDeclared ? (
                <Actions
                    label={`Open actions for VLAN ${vlan.tag}`}
                    onEdit={() =>
                        openVlanModal('edit', { networkInterface, vlan })
                    }
                    onDelete={() =>
                        openVlanModal('delete', { networkInterface, vlan })
                    }
                />
            ) : (
                <Button
                    size={'sm'}
                    variant={'ghost'}
                    onClick={() =>
                        openVlanModal('create', { networkInterface, vlan })
                    }
                >
                    Declare
                </Button>
            )}
        </div>
    )
}

const NetworkInterfaceCard = ({ interface: networkInterface }: Props) => {
    const openModal = useOpenModal(useNetworkInterfacesModalStore)
    const openVlanModal = useOpenModal(useVlansModalStore)

    const header = (
        <div className={'flex min-w-0 flex-1 flex-wrap items-center gap-2'}>
            <span className={'truncate font-semibold'}>
                {networkInterface.name}
            </span>
            {networkInterface.isVlanAware ? (
                <Badge variant={'secondary'} className={'w-fit'}>
                    Trunk
                </Badge>
            ) : (
                // The common case states itself in muted meta text rather than
                // a badge: a badge marks an exception, and almost every bridge
                // is untagged.
                <span className={'text-muted-foreground text-sm'}>
                    Untagged
                </span>
            )}
            {networkInterface.description && (
                <span className={'text-muted-foreground truncate text-sm'}>
                    {networkInterface.description}
                </span>
            )}
        </div>
    )

    const actions = (
        <Actions
            label={`Open actions for ${networkInterface.name}`}
            onEdit={() => openModal('edit', networkInterface)}
            onDelete={() => openModal('delete', networkInterface)}
        />
    )

    // A bridge that doesn't trunk can never have children, so it gets no
    // disclosure control — an always-empty expander is just a dead affordance.
    if (!networkInterface.isVlanAware) {
        return (
            <div className={'flex items-center gap-3 px-3 py-2.5'}>
                {/* Matches the trigger's own chevron + gap-1.5 so every bridge
                    name in the list starts at the same x. */}
                <div className={'flex min-w-0 flex-1 items-center gap-1.5'}>
                    <span className={'size-4 shrink-0'} aria-hidden />
                    {header}
                </div>
                <InterfaceMeta networkInterface={networkInterface} />
                {actions}
            </div>
        )
    }

    return (
        <Collapsible defaultOpen={networkInterface.vlans.length > 0}>
            <div className={'flex items-center gap-3 px-3 py-2.5'}>
                <CollapsibleTrigger
                    className={cn(
                        'text-foreground min-w-0 flex-1',
                        'font-normal'
                    )}
                >
                    {header}
                </CollapsibleTrigger>
                <InterfaceMeta networkInterface={networkInterface} />
                {actions}
            </div>
            <CollapsiblePanel>
                <div className={'space-y-0 pb-2 pl-7'}>
                    {networkInterface.vlans.map((vlan, index) => (
                        <VlanRow
                            key={vlan.tag}
                            vlan={vlan}
                            networkInterface={networkInterface}
                            isLast={index === networkInterface.vlans.length - 1}
                        />
                    ))}
                    {networkInterface.vlans.length === 0 && (
                        <p className={'text-muted-foreground py-1.5 text-sm'}>
                            No VLANs on this trunk yet.
                        </p>
                    )}
                    <Button
                        size={'sm'}
                        variant={'ghost'}
                        className={'text-muted-foreground mt-1 ml-4'}
                        onClick={() =>
                            openVlanModal('create', {
                                networkInterface,
                                vlan: null,
                            })
                        }
                    >
                        <IconPlus className={'size-4'} /> Declare VLAN
                    </Button>
                </div>
            </CollapsiblePanel>
        </Collapsible>
    )
}

export default NetworkInterfaceCard
