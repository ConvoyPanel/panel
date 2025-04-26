import { NodeStorage } from '@/types/storage.ts'
import { cn } from '@/utils'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { IconGripVertical } from '@tabler/icons-react'

import { badgeVariants } from '@/components/ui/Badge.tsx'

interface Props {
    storage: NodeStorage
    isOverlay?: boolean
}

const SortableStorageCard = ({ storage, isOverlay = false }: Props) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: storage.id,
    })

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    }

    return (
        <li
            className={cn(
                'relative flex flex-col rounded-lg border bg-card py-2 pl-7 pr-2 text-card-foreground shadow select-none',
                isOverlay && 'z-10',
                isDragging && 'opacity-50 brightness-50'
            )}
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
        >
            <IconGripVertical
                className={
                    'absolute left-1 top-1/2 shrink-0 -translate-y-1/2 text-muted-foreground'
                }
                stroke={1.5}
            />
            <div className={'flex items-center'}>
                <p className={'inline truncate font-semibold'}>
                    {storage?.displayName ?? storage.name}
                </p>
                {storage.displayName && (
                    <span
                        className={cn(
                            'shrink-0 ml-1.5 inline-flex align-middle tracking-tight',
                            badgeVariants({
                                variant: 'secondary',
                            })
                        )}
                    >
                        {storage.name}
                    </span>
                )}
            </div>
            {storage.description && (
                <p className={'text-ellipsis text-sm text-muted-foreground'}>
                    {storage.description}
                </p>
            )}
        </li>
    )
}

export default SortableStorageCard
