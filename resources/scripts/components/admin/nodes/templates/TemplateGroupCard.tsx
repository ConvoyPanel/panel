import { TemplateGroup } from '@/api/admin/nodes/templates/getTemplates'
import Card from '@/components/elements/Card'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
import { classNames } from '@/util/helpers'

interface Props {
    group: TemplateGroup
    className?: string
}

const TemplateGroupCard = ({ group, className }: Props) => {
    return (
        <SortableItem overrideZIndex handle id={group.id}>
            {({ attributes, listeners, isDragging }: ChildrenPropsWithHandle) => (
                <Card overridePadding className={classNames('min-h-[9rem] relative', className)} key={group.id}>
                    {isDragging && <div className='inset-0 z-10 absolute bg-accent-200' />}
                    <div className='touch-none' {...attributes} {...listeners}>
                        <p className='px-4 pt-3 font-medium text-foreground select-none'>{group.name}</p>
                    </div>
                    <div className='px-4 pb-4 relative'>
                        <div className='flex flex-col space-y-3 mt-2'>
                            {group.templates.map(template => (
                                <div className='px-3 py-2 bg-accent-200 rounded' key={template.id}>
                                    <p className='font-medium text-sm text-foreground'>{template.name}</p>
                                    <p className='description-small !text-xs'>vmid: {template.vmid}</p>
                                </div>
                            ))}

                            <button className='text-sm bg-transparent active:bg-accent-200 sm:hover:bg-accent-100 transition-colors text-foreground border border-accent-400 border-dashed rounded py-2'>
                                New Template
                            </button>
                        </div>
                    </div>
                </Card>
            )}
        </SortableItem>
    )
}

export default TemplateGroupCard
