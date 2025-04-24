import { NodeStorage } from '@/types/storage.ts'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { cn } from '@/utils'

interface Props {
    storage: NodeStorage
    isOverlay?: boolean
}

const SortableStorageCard = ({ storage, isOverlay = false }: Props) => {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
        useSortable({
            id: storage.id,
        })

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    }

    return (
        <li
            className={cn('rounded-md py-2 px-4 border bg-card text-card-foreground shadow', isOverlay && 'z-10', isDragging && 'brightness-50 opacity-50')}
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
        >
            <p>{storage?.displayName ?? storage.name}</p>
        </li>
    )
}

export default SortableStorageCard
