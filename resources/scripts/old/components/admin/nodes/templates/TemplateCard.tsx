import { classNames } from '@/util/helpers'
import useFlash from '@/util/useFlash'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { useState } from 'react'

import {
    Template,
    TemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import deleteTemplate from '@/api/admin/nodes/templateGroups/templates/deleteTemplate'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import DottedButton from '@/components/elements/DottedButton'
import Menu from '@/components/elements/Menu'
import SortableItem, {
    ChildrenPropsWithHandle,
} from '@/components/elements/dnd/SortableItem'

import EditTemplateModal from '@/components/admin/nodes/templates/EditTemplateModal'


interface Props {
    template: Template
    group?: TemplateGroup
    className?: string
}

const TemplateCard = ({ template, group, className }: Props) => {
    const [showEditModal, setShowEditModal] = useState(false)
    const { data: node } = useNodeSWR()
    const { mutate } = useTemplateGroupsSWR(node.id)
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const handleDelete = async () => {
        clearFlashes('admin:node:template-groups')

        try {
            await deleteTemplate(node.id, group!.uuid, template.uuid)

            mutate(
                groups =>
                    groups!.map(g => {
                        if (g.id === group!.id) {
                            // return it
                            return {
                                ...g,
                                templates: g.templates!.filter(
                                    t => t.id !== template.id
                                ),
                            }
                        }

                        return g
                    }),
                false
            )
        } catch (error) {
            clearAndAddHttpError({ key: 'admin:node:template-groups', error })
        }
    }

    return (
        <SortableItem overrideZIndex handle id={template.id}>
            {({
                attributes,
                listeners,
                isDragging,
            }: ChildrenPropsWithHandle) => (
                <div
                    className={classNames(
                        'flex justify-between items-center px-3 py-2 rounded select-none',
                        isDragging ? 'bg-accent-100' : 'bg-accent-200',
                        className
                    )}
                >
                    <EditTemplateModal
                        group={group!}
                        template={template}
                        open={showEditModal}
                        onClose={() => setShowEditModal(false)}
                    />
                    <div
                        className={classNames(
                            'grow',
                            isDragging ? 'invisible' : null
                        )}
                        {...attributes}
                        {...listeners}
                    >
                        <div className='flex space-x-3 items-center overflow-hidden'>
                            <p className='font-medium text-sm text-foreground truncate'>
                                {template.name}
                            </p>
                            {template.hidden && (
                                <EyeSlashIcon
                                    title='hidden'
                                    className='h-4 w-4 text-foreground'
                                />
                            )}
                        </div>
                        <p className='description-small !text-xs'>
                            vmid: {template.vmid}
                        </p>
                    </div>
                    <Menu width={200}>
                        <Menu.Target>
                            <DottedButton />
                        </Menu.Target>
                        <Menu.Dropdown>
                            <Menu.Item
                                onClick={() =>
                                    navigator.clipboard.writeText(template.uuid)
                                }
                            >
                                Copy ID
                            </Menu.Item>
                            <Menu.Item onClick={() => setShowEditModal(true)}>
                                Edit
                            </Menu.Item>
                            <Menu.Divider />
                            <Menu.Item color='red' onClick={handleDelete}>
                                Delete
                            </Menu.Item>
                        </Menu.Dropdown>
                    </Menu>
                </div>
            )}
        </SortableItem>
    )
}

export default TemplateCard