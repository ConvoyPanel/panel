import { Template, TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import deleteTemplate from '@/api/admin/nodes/templateGroups/templates/deleteTemplate'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import EditTemplateModal from '@/components/admin/nodes/templates/EditTemplateModal'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
import Menu from '@/components/elements/Menu'
import { DottedButton } from '@/components/servers/backups/BackupRow'
import { NodeContext } from '@/state/admin/node'
import { classNames } from '@/util/helpers'
import useFlash from '@/util/useFlash'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { useState } from 'react'

interface Props {
    template: Template
    group?: TemplateGroup
    className?: string
}

const TemplateCard = ({ template, group, className }: Props) => {
    const [showEditModal, setShowEditModal] = useState(false)
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { mutate } = useTemplateGroupsSWR(nodeId)
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const handleDelete = async () => {
        clearFlashes('admin:node:template-groups')

        try {
            await deleteTemplate(nodeId, group!.uuid, template.uuid)

            mutate(
                groups =>
                    groups!.map(g => {
                        if (g.id === group!.id) {
                            // return it
                            return {
                                ...g,
                                templates: g.templates!.filter(t => t.id !== template.id),
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
            {({ attributes, listeners, isDragging }: ChildrenPropsWithHandle) => (
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
                    <div className={classNames('grow', isDragging ? 'invisible' : null)} {...attributes} {...listeners}>
                        <div className='flex space-x-3 items-center'>
                            <p className='font-medium text-sm text-foreground'>{template.name}</p>
                            {template.hidden && <EyeSlashIcon title='hidden' className='h-4 w-4 text-foreground' />}
                        </div>
                        <p className='description-small !text-xs'>vmid: {template.vmid}</p>
                    </div>
                    <Menu>
                        <Menu.Button>
                            <DottedButton />
                        </Menu.Button>
                        <Menu.Items>
                            <Menu.Item onClick={() => setShowEditModal(true)}>Edit</Menu.Item>
                            <Menu.Divider />
                            <Menu.Item color='danger' onClick={handleDelete}>
                                Delete
                            </Menu.Item>
                        </Menu.Items>
                    </Menu>
                </div>
            )}
        </SortableItem>
    )
}

export default TemplateCard
