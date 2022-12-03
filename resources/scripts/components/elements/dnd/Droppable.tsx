import React, { Fragment, ReactNode } from 'react'
import { useDroppable } from '@dnd-kit/core'

interface RenderFuncProps {
    isOver: boolean
}

interface Props {
    id: string | number
    className?: string
    children: ((props: RenderFuncProps) => ReactNode) | ReactNode
}

const Droppable = ({ id, className, children }: Props) => {
    const { isOver, setNodeRef } = useDroppable({
        id,
    })

    return (
        <div ref={setNodeRef} className={className}>
            {children instanceof Function ? children({ isOver }) : children}
        </div>
    )
}

export default Droppable