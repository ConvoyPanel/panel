import useNetworkInterfacesModalStore from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { NetworkInterface } from '@/types/network-interface.ts'
import { IconDots } from '@tabler/icons-react'

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
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    interface: NetworkInterface
}

const NetworkInterfaceCard = ({ interface: networkInterface }: Props) => {
    const openModal = useOpenModal(useNetworkInterfacesModalStore)

    const vlanLabel = networkInterface.vlanTag
        ? `VLAN ${networkInterface.vlanTag}`
        : networkInterface.isVlanAware
          ? 'Untagged'
          : 'Not VLAN-aware'

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle>
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
                <dl>
                    <div>
                        <dt className={'text-muted-foreground text-xs'}>
                            VLAN
                        </dt>
                        <dd className={'text-sm'}>{vlanLabel}</dd>
                    </div>
                </dl>
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
