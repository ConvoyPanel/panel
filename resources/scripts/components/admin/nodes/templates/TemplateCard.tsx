import { Template } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'

interface Props {
    template: Template
}

const TemplateCard = ({ template }: Props) => {
    return (
        <SortableItem overrideZIndex handle id={template.uuid}>
            {({ attributes, listeners, isDragging, active }: ChildrenPropsWithHandle) => (
                <div className='px-3 py-2 bg-accent-200 rounded' {...attributes} {...listeners}>
                    <p className='font-medium text-sm text-foreground'>{template.name}</p>
                    <p className='description-small !text-xs'>vmid: {template.vmid}</p>
                </div>
            )}
        </SortableItem>
    )
}

export default TemplateCard
