import { TemplateGroup } from '@/types/template-group.ts'
import { IconCopy, IconDots, IconSquaresDiagonal } from '@tabler/icons-react'
import { useShallow } from 'zustand/react/shallow'

import TemplateIconDisplay from '@/components/interfaces/Admin/Template/TemplateIconDisplay.tsx'
import useTemplateGroupsModalStore from '@/components/interfaces/Admin/Template/use-template-groups-modal-store.ts'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    group: TemplateGroup
}

const TemplateGroupCard = ({ group }: Props) => {
    const openModal = useTemplateGroupsModalStore(
        useShallow(state => state.openModal)
    )

    return (
        <Card className={'flex items-center px-3 py-2'}>
            <TemplateIconDisplay
                icon={group.icon}
                defaultIcon={IconCopy}
                className={'mr-1.5 size-8 shrink-0'}
            />
            <div
                className={'flex grow flex-col justify-center overflow-hidden'}
            >
                <button
                    className={
                        'inline-block text-left font-semibold leading-tight'
                    }
                    title={`Edit details for ${group.name}`}
                    onClick={() => openModal('edit', group)}
                >
                    {group.name}
                </button>
                {group.description && (
                    <p
                        className={
                            'text-ellipsis whitespace-nowrap text-sm text-muted-foreground'
                        }
                    >
                        {group.description}
                    </p>
                )}
            </div>
            <div
                className={
                    'flex shrink-0 items-center justify-end justify-items-end'
                }
            >
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button size={'icon'} variant={'ghost'}>
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
                            onClick={() => openModal('delete', group)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </Card>
    )
}

export default TemplateGroupCard
