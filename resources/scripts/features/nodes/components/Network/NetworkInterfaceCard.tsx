import { NetworkInterface } from '@/types/network-interface.ts'

import { Card } from '@/components/ui/Card'
import useNetworkInterfacesModalStore
    from '@/features/nodes/hooks/use-network-interfaces-modal-store.ts'
import { useShallow } from 'zustand/react/shallow'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/DropdownMenu'
import { Button } from '@/components/ui/Button'
import { IconDots } from '@tabler/icons-react'
import { StatLabel } from '@/components/ui/Typography'

interface Props {
    interface: NetworkInterface
}

const NetworkInterfaceCard = ({ interface: networkInterface }: Props) => {
    const openModal = useNetworkInterfacesModalStore(
        useShallow(state => state.openModal)
    )

    const vlanLabel = networkInterface.vlanTag
        ? `VLAN ${networkInterface.vlanTag}`
        : networkInterface.isVlanAware
          ? 'Untagged'
          : 'Not VLAN-aware'

    return (
        <Card className={'flex px-5 py-2.5 pr-2.5'}>
            <div className={'flex grow flex-col justify-center'}>
                <button
                    className={'inline-block text-left font-semibold'}
                    title={`Edit details for ${networkInterface.name}`}
                    onClick={() => openModal('edit', networkInterface)}
                >
                    {networkInterface.name}
                </button>
                {networkInterface.description && (
                    <p
                        className={
                            'text-ellipsis text-sm text-muted-foreground'
                        }
                    >
                        {networkInterface.description}
                    </p>
                )}
                <dl className={'mt-2 flex flex-wrap gap-4 text-sm'}>
                    <div>
                        <StatLabel as={'dt'} className={'text-xs'}>
                            VLAN
                        </StatLabel>
                        <dd>{vlanLabel}</dd>
                    </div>
                </dl>
            </div>
            <div className={'flex items-center justify-end justify-items-end'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button size={'icon'} variant={'ghost'}>
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
                            onClick={() => openModal('delete', networkInterface)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </Card>
    )
}

export default NetworkInterfaceCard
