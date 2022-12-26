import { DraggableAttributes } from '@dnd-kit/core';
import { SyntheticListenerMap } from '@dnd-kit/core/dist/hooks/utilities';
import {useSortable} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';
import { ReactNode, useEffect } from 'react';

type BaseProps = {
  id: string | number
  className?: string
}

type PropsWithoutHandle = {
  handle?: false
  children: ReactNode
} & BaseProps

type PropsWithHandle = {
  handle: true
  children: (attributes: DraggableAttributes, listeners?: SyntheticListenerMap) => ReactNode
} & BaseProps

const SortableItem = ({ id, handle, className, children }: PropsWithoutHandle | PropsWithHandle) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    active,
  } = useSortable({id});

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  const rootAttributes = !handle ? {...attributes, ...listeners} : undefined

  return (
    <div ref={setNodeRef} className={`${className} ${!handle && 'touch-none'} ${active?.id === id && 'z-[2000]'}`} style={style} {...rootAttributes}>
      { handle === true ? children(attributes, listeners) : children}
    </div>
  );
}

export default SortableItem