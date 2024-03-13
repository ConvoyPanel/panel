import { Active, DraggableAttributes } from '@dnd-kit/core'
import { SyntheticListenerMap } from '@dnd-kit/core/dist/hooks/utilities'
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { ReactNode } from 'react'


type BaseProps = {
    id: string | number
    className?: string
    overrideZIndex?: boolean
}

type PropsWithoutHandle = {
    handle?: false
    children: ReactNode | ((props: ChildrenPropsWithoutHandle) => ReactNode)
} & BaseProps

type PropsWithHandle = {
    handle: true
    children: (props: ChildrenPropsWithHandle) => JSX.Element
} & BaseProps

export type ChildrenPropsWithoutHandle = {
    isDragging: boolean
    active: Active | null
}

export type ChildrenPropsWithHandle = {
    attributes: DraggableAttributes
    listeners?: SyntheticListenerMap
} & ChildrenPropsWithoutHandle

const SortableItem = ({
    id,
    handle,
    className,
    children,
    overrideZIndex,
}: PropsWithoutHandle | PropsWithHandle) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
        active,
    } = useSortable({ id })

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    }

    const rootAttributes = !handle ? { ...attributes, ...listeners } : undefined

    return (
        <div
            ref={setNodeRef}
            className={`${className} ${!handle && 'touch-none'} ${
                isDragging && !overrideZIndex ? 'z-[2000]' : ''
            }`}
            style={style}
            {...rootAttributes}
        >
            {handle
                ? children({ attributes, listeners, isDragging, active })
                : typeof children === 'function'
                ? children({ isDragging, active })
                : children}
        </div>
    )
}

export default SortableItem