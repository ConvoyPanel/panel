import { NodeStorage } from '@/types/storage.ts'
import { cn } from '@/utils'
import { IconDots } from '@tabler/icons-react'
import { useShallow } from 'zustand/react/shallow'

import useStoragesModalStore from '@/components/interfaces/Admin/Node/Storages/use-storages-modal-store.ts'

import { Badge, badgeVariants } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    storage: NodeStorage
}

const StorageCard = ({ storage }: Props) => {
    const openModal = useStoragesModalStore(
        useShallow(state => state.openModal)
    )

    return (
        <Card className={'flex px-5 py-2.5 pr-2.5'}>
            <div className={'flex grow flex-col justify-center'}>
                <button
                    className={'inline-block text-left font-semibold'}
                    onClick={() => openModal('show', storage)}
                    title={`View details for ${storage.displayName ?? storage.name}`}
                >
                    {storage.displayName ?? storage.name}{' '}
                    {storage.displayName && (
                        <span
                            className={cn(
                                'inline-flex align-middle tracking-tight',
                                badgeVariants({
                                    variant: 'secondary',
                                })
                            )}
                        >
                            {storage.name}
                        </span>
                    )}
                </button>
                {storage.description && (
                    <p
                        className={
                            'text-ellipsis text-sm text-muted-foreground'
                        }
                    >
                        {storage.description}
                    </p>
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
                            onClick={() => openModal('delete', storage)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </Card>
    )
}

export default StorageCard
