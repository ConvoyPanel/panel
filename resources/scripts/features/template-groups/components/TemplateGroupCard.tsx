import { TemplateGroup } from '@/types/template-group.ts'
import { IconCopy, IconDots } from '@tabler/icons-react'
import { useShallow } from 'zustand/react/shallow'

import TemplateIconDisplay from '@/features/template-groups/components/TemplateIconDisplay.tsx'
import useTemplateGroupsModalStore from '@/features/template-groups/hooks/use-template-groups-modal-store.ts'

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
    group: TemplateGroup
}

const TemplateGroupCard = ({ group }: Props) => {
    const openModal = useTemplateGroupsModalStore(
        useShallow(state => state.openModal)
    )

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <TemplateIconDisplay
                    icon={group.icon}
                    defaultIcon={IconCopy}
                    className={'size-4'}
                />
            </ItemMedia>
            <ItemContent className={'min-w-0 overflow-x-hidden'}>
                <ItemTitle className={'max-w-full'}>
                    <button
                        className={'truncate text-left'}
                        title={`View child templates for ${group.name}`}
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
                            aria-label={'Open template group actions'}
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

export default TemplateGroupCard
