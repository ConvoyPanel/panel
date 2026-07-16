import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { IconDots } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge.tsx'
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
    storage: NodeStorage
}

const StorageCard = ({ storage }: Props) => {
    const openModal = useOpenModal(useStoragesModalStore)

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'max-w-full'}>
                    <button
                        className={'truncate text-left'}
                        onClick={() => openModal('show', storage)}
                        title={`View details for ${storage.displayName ?? storage.name}`}
                    >
                        {storage.displayName ?? storage.name}
                    </button>
                    {storage.displayName && (
                        <Badge variant={'secondary'} className={'shrink-0'}>
                            {storage.name}
                        </Badge>
                    )}
                </ItemTitle>
                {storage.description && (
                    <ItemDescription className={'truncate'}>
                        {storage.description}
                    </ItemDescription>
                )}
                <div className={'mt-1 flex flex-wrap gap-1'}>
                    {storage.storesKvm && (
                        <Badge variant={'secondary'}>KVM</Badge>
                    )}
                    {storage.storesLxc && (
                        <Badge variant={'secondary'}>LXC</Badge>
                    )}
                    {storage.storesLxcTemplates && (
                        <Badge variant={'secondary'}>LXC Templates</Badge>
                    )}
                    {storage.storesBackups && (
                        <Badge variant={'secondary'}>Backups</Badge>
                    )}
                    {storage.storesIso && (
                        <Badge variant={'secondary'}>ISO</Badge>
                    )}
                    {storage.storesSnippets && (
                        <Badge variant={'secondary'}>Snippets</Badge>
                    )}
                </div>
            </ItemContent>
            <ItemActions className={'ml-auto'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            size={'icon'}
                            variant={'ghost'}
                            aria-label={'Open storage actions'}
                        >
                            <IconDots className={'text-muted-foreground'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem
                            onClick={() => openModal('show', storage)}
                        >
                            Usage
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => openModal('edit', storage)}
                        >
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={() => openModal('delete', storage)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </ItemActions>
        </Item>
    )
}

export default StorageCard
