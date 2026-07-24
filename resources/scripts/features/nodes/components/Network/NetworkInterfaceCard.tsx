import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { NetworkInterface } from '@/types/network-interface.ts'
import { IconDots, IconNetwork } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    interface: NetworkInterface
}

const NetworkInterfaceCard = ({ interface: networkInterface }: Props) => {
    const openModal = useOpenModal(useNetworkInterfacesModalStore)

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <IconNetwork />
            </ItemMedia>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>
                    <button
                        className={'truncate text-left'}
                        title={`Edit details for ${networkInterface.name}`}
                        onClick={() => openModal('edit', networkInterface)}
                    >
                        {networkInterface.name}
                    </button>
                </ItemTitle>
                {networkInterface.description && (
                    <ItemDescription className={'truncate'}>
                        {networkInterface.description}
                    </ItemDescription>
                )}
                <div className={'mt-0.5 flex flex-wrap items-center gap-1.5'}>
                    <Badge
                        variant={
                            networkInterface.isVlanAware
                                ? 'secondary'
                                : 'outline'
                        }
                        className={'w-fit'}
                    >
                        {networkInterface.isVlanAware
                            ? 'VLAN-aware'
                            : 'Not VLAN-aware'}
                    </Badge>
                    {networkInterface.vlanTag != null && (
                        <Badge
                            variant={'outline'}
                            className={'w-fit font-mono'}
                        >
                            VLAN {networkInterface.vlanTag}
                        </Badge>
                    )}
                </div>
            </ItemContent>
            <ItemActions className={'ml-auto'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            size={'icon'}
                            variant={'ghost'}
                            aria-label={'Open network interface actions'}
                        >
                            <IconDots className={'text-muted-foreground'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem
                            onClick={() => openModal('edit', networkInterface)}
                        >
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={() =>
                                openModal('delete', networkInterface)
                            }
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </ItemActions>
        </Item>
    )
}

export default NetworkInterfaceCard
