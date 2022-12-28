import { Template, TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import Card from '@/components/elements/Card'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
import { DottedButton } from '@/components/servers/backups/BackupRow'
import { classNames } from '@/util/helpers'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
//@ts-ignore
import Dots from '@/assets/images/icons/dots-vertical.svg'
import Menu from '@/components/elements/Menu'
import EditTemplateGroupModal from '@/components/admin/nodes/templates/EditTemplateGroupModal'
import { useState } from 'react'
import deleteTemplateGroup from '@/api/admin/nodes/templateGroups/deleteTemplateGroup'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import {
    DndContext,
    DragEndEvent,
    DragOverlay,
    DragStartEvent,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core'
import { SortableContext } from '@dnd-kit/sortable'
import TemplateCard from '@/components/admin/nodes/templates/TemplateCard'
import { restrictToVerticalAxis, restrictToWindowEdges } from '@dnd-kit/modifiers'
import EditTemplateModal from '@/components/admin/nodes/templates/EditTemplateModal'

interface Props {
    group: TemplateGroup
    className?: string
}

const TemplateGroupCard = ({ group, className }: Props) => {
    const [showEditModal, setShowEditModal] = useState(false)
    const [showCreateModal, setShowCreateModal] = useState(false)
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const { mutate } = useTemplateGroupsSWR(nodeId)
    const [activeTemplate, setActiveTemplate] = useState<Template | undefined>()

    const mouseSensor = useSensor(MouseSensor, {
        activationConstraint: {
            delay: 100,
            tolerance: 5,
        },
    })
    const keyboardSensor = useSensor(KeyboardSensor)
    const sensors = useSensors(mouseSensor, keyboardSensor)

    const handleDelete = () => {
        clearFlashes('admin:node:template-groups')

        deleteTemplateGroup(nodeId, group.uuid)
            .then(() => {
                mutate(groups => groups!.filter(g => g.id !== group.id), false)
            })
            .catch(error => {
                clearAndAddHttpError({
                    key: 'admin:node:template-groups',
                    error,
                })
            })
    }

    const handleDragStart = (event: DragStartEvent) => {
        setActiveTemplate(group.templates!.find(template => template.id === event.active.id)!)

    }

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        setActiveTemplate(undefined)
        if (over && active.id !== over.id) {
        }
    }

    return (
        <SortableItem overrideZIndex handle id={group.id}>
            {({ attributes, listeners, isDragging, active }: ChildrenPropsWithHandle) => (
                <Card
                    overridePadding
                    className={classNames('min-h-[9rem] relative select-none', className)}
                    key={group.id}
                >
                    <EditTemplateModal group={group} open={showCreateModal} onClose={() => setShowCreateModal(false)} />
                    <EditTemplateGroupModal
                        group={group}
                        open={showEditModal}
                        onClose={() => setShowEditModal(false)}
                    />
                    {isDragging || active ? (
                        <div
                            className={classNames(
                                'inset-0 z-10 absolute bg-accent-200',
                                !isDragging && active ? 'opacity-50' : undefined
                            )}
                        />
                    ) : null}
                    <div className='flex justify-between items-center pt-3 px-4'>
                        <div className='flex items-center space-x-3 grow' {...attributes} {...listeners}>
                            <p className='font-medium text-foreground'>{group.name}</p>
                            {group.hidden && <EyeSlashIcon title='hidden' className='h-5 w-5 text-foreground' />}
                        </div>
                        <Menu>
                            <Menu.Button>
                                <img
                                    src={Dots}
                                    className='w-4 h-4 min-w-[1rem] dark:invert cursor-pointer'
                                    alt='3 vertical dots meant for activating a menu'
                                />
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
                    <div className='px-4 pb-4 relative'>
                        <div className='flex flex-col space-y-3 mt-2'>
                            {group.templates!.length > 0 ? (
                                <DndContext sensors={sensors} modifiers={[restrictToVerticalAxis, restrictToWindowEdges]} onDragEnd={handleDragEnd} onDragStart={handleDragStart}>
                                    <SortableContext items={group.templates!}>
                                        {group.templates!.map(template => (
                                            <TemplateCard group={group} key={template.uuid} template={template} />
                                        ))}
                                    </SortableContext>

                                    <DragOverlay>
                                        {activeTemplate ? <TemplateCard template={activeTemplate} className='z-20' /> : null}
                                    </DragOverlay>
                                </DndContext>
                            ) : null}

                            {group.templates!.length < 6 ? <button onClick={() => setShowCreateModal(true)} className='text-sm bg-transparent active:bg-accent-200 sm:hover:bg-accent-100 transition-colors text-foreground border border-accent-400 border-dashed rounded py-2'>
                                New Template
                            </button> : null}
                        </div>
                    </div>
                </Card>
            )}
        </SortableItem>
    )
}

export default TemplateGroupCard
