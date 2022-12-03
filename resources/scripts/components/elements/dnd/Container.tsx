import { useDroppable } from '@dnd-kit/core'
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable'

import SortableItem from '@/components/elements/dnd/SortableItem'

interface Props {
    id: string
    items: Array<any>
}

const Container = ({ id, items }: Props) => {
    const { setNodeRef } = useDroppable({
        id,
    })

    return (
        <SortableContext id={id} items={items} strategy={verticalListSortingStrategy}>
            <div
                ref={setNodeRef}
                style={{
                    background: '#dadada',
                    padding: 10,
                    margin: 10,
                    flex: 1,
                }}
            >
                {items.map(id => (
                    <SortableItem key={id} id={id} />
                ))}
            </div>
        </SortableContext>
    )
}

export default Container
