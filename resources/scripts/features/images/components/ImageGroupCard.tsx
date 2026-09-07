import ImageIconDisplay from '@/features/images/components/ImageIconDisplay.tsx'
import useImageGroupsModalStore from '@/features/images/hooks/use-image-groups-modal-store.ts'
import { useOpenModal } from '@/hooks/create-modal-store.ts'
import { ImageGroup } from '@/types/image.ts'
import { IconCopy, IconDots } from '@tabler/icons-react'

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
    group: ImageGroup
}

const ImageGroupCard = ({ group }: Props) => {
    const openModal = useOpenModal(useImageGroupsModalStore)

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <ImageIconDisplay
                    icon={group.icon}
                    defaultIcon={IconCopy}
                    className={'size-4'}
                />
            </ItemMedia>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'max-w-full'}>
                    <button
                        className={'truncate text-left'}
                        title={`View child definitions for ${group.name}`}
                        onClick={() => openModal('show', group)}
                    >
                        {group.name}
                    </button>
                    {group.isAdminOnly && (
                        <Badge variant={'secondary'} className={'shrink-0'}>
                            Admin only
                        </Badge>
                    )}
                </ItemTitle>
                {group.description && (
                    <ItemDescription className={'truncate'}>
                        {group.description}
                    </ItemDescription>
                )}
            </ItemContent>
            <ItemActions className={'ml-auto'}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            size={'icon'}
                            variant={'ghost'}
                            aria-label={'Open image group actions'}
                        >
                            <IconDots className={'text-muted-foreground'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem
                            onClick={() => openModal('edit', group)}
                        >
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => openModal('show', group)}
                        >
                            View
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={() => openModal('delete', group)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </ItemActions>
        </Item>
    )
}

export default ImageGroupCard
