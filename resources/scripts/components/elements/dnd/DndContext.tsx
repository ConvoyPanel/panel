import { DragEndEvent, DragOverlay } from '@dnd-kit/core'
import { Props as DndKitContextProps } from '@dnd-kit/core/dist/components/DndContext/DndContext'
import { ReactNode, useState } from 'react'

interface Props extends DndKitContextProps {
    dragOverlay?: (activeId?: string | number) => ReactNode
}

const DndContext = ({ dragOverlay, children, onDragStart, onDragEnd, ...props }: Props) => {
    const [activeId, setActiveId] = useState<string | number | undefined>()

    const handleDragStart = (event: DragEndEvent) => {
        setActiveId(event.active.id)
        onDragStart?.(event)
    }

    const handleDragEnd = (event: DragEndEvent) => {
        setActiveId(undefined)
        onDragEnd?.(event)
    }

    return (
        <DndContext onDragStart={handleDragStart} onDragEnd={handleDragEnd} {...props}>
            {children}
            {dragOverlay && <DragOverlay>{dragOverlay(activeId)}</DragOverlay>}
        </DndContext>
    )
}

export default DndContext
