import { useDraggable, UseDraggableArguments } from '@dnd-kit/core'
import { ReactNode } from 'react'

interface Props {
    id: string | number
    children: ReactNode
    attributes?: UseDraggableArguments['attributes']
    data?: UseDraggableArguments['data']
    disabled?: boolean
}

const Draggable = ({ id, children, attributes: propAttributes, data, disabled }: Props) => {
    const { attributes, active, listeners, setNodeRef, transform } = useDraggable({
        id,
        attributes: propAttributes,
        data,
        disabled,
    })
    const style = transform
        ? {
              transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
          }
        : undefined

    return <button ref={setNodeRef} style={style} {...listeners} {...attributes}>
        { children }
    </button>
}

export default Draggable
