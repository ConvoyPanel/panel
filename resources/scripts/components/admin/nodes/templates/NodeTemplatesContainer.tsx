import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import {
    DndContext,
    DragEndEvent,
    DragOverlay,
    DragStartEvent,
    KeyboardSensor,
    MouseSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core'
import { restrictToWindowEdges } from '@dnd-kit/modifiers'
import { SortableContext, arrayMove } from '@dnd-kit/sortable'
import { updateNotification } from '@mantine/notifications'
import { useState } from 'react'

import { TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import reorderTemplateGroups from '@/api/admin/nodes/templateGroups/reorderTemplateGroups'
import useTemplatesGroupSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'
import { httpErrorToHuman } from '@/api/http'

import Button from '@/components/elements/Button'
import Spinner from '@/components/elements/Spinner'

import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import EditTemplateGroupModal from '@/components/admin/nodes/templates/EditTemplateGroupModal'
import TemplateGroupCard from '@/components/admin/nodes/templates/TemplateGroupCard'

const NodeTemplatesContainer = () => {
    const { data: node } = useNodeSWR()
    const { data, mutate } = useTemplatesGroupSWR(node.id, [])
    const notify = useNotify()
    const [showCreateModal, setShowCreateModal] = useState(false)
    const [activeId, setActiveId] = useState<TemplateGroup | undefined>()
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const mouseSensor = useSensor(MouseSensor)
    const keyboardSensor = useSensor(KeyboardSensor)
    const sensors = useSensors(mouseSensor, keyboardSensor)

    const handleDragStart = (event: DragStartEvent) => {
        setActiveId(data!.find(group => group.id === event.active.id)!)
    }

    const updateGroupOrder = (groups: number[]) => {
        clearFlashes('admin:node:template-groups')

        notify({
            id: 'admin:node:template-groups.reorder',
            loading: true,
            message: 'Saving changes...',
            autoClose: false,
            disallowClose: true,
        })

        reorderTemplateGroups(node.id, groups)
            .then(() => {
                updateNotification({
                    id: 'admin:node:template-groups.reorder',
                    message: 'Saved order',
                    autoClose: 1000,
                })
            })
            .catch(error => {
                updateNotification({
                    id: 'admin:node:template-groups.reorder',
                    color: 'red',
                    message: httpErrorToHuman(error),
                    autoClose: 5000,
                })
                clearAndAddHttpError({
                    key: 'admin:node:template-groups',
                    error,
                })
            })
    }

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        setActiveId(undefined)
        if (over && active.id !== over.id) {
            mutate(groups => {
                // move the new group to the correct position and push everything down by 1
                const newGroups = arrayMove(
                    groups!,
                    groups!.findIndex(group => group.id === active.id),
                    groups!.findIndex(group => group.id === over.id)
                )
                newGroups.forEach(
                    (group, index) => (group.orderColumn = index + 1)
                )

                updateGroupOrder(newGroups.map(group => group.id))

                return newGroups
            }, false)
        }
    }

    return (
        <NodeContentBlock
            title='Templates'
            showFlashKey='admin:node:template-groups'
        >
            <EditTemplateGroupModal
                open={showCreateModal}
                onClose={() => setShowCreateModal(false)}
            />
            <div className='flex justify-end items-center mb-3'>
                <Button
                    onClick={() => setShowCreateModal(true)}
                    disabled={data!.length >= 50}
                    variant='filled'
                >
                    New Template Group
                </Button>
            </div>
            {!data ? (
                <Spinner />
            ) : (
                <div className='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3'>
                    <DndContext
                        sensors={sensors}
                        onDragEnd={handleDragEnd}
                        modifiers={[restrictToWindowEdges]}
                        onDragStart={handleDragStart}
                    >
                        <SortableContext items={data}>
                            {data!.map(group => (
                                <TemplateGroupCard
                                    group={group}
                                    key={group.id}
                                />
                            ))}
                        </SortableContext>
                        <DragOverlay>
                            {activeId && (
                                <TemplateGroupCard
                                    group={activeId}
                                    className='z-20'
                                />
                            )}
                        </DragOverlay>
                    </DndContext>
                    {data.length < 50 && (
                        <button
                            onClick={() => setShowCreateModal(true)}
                            className='text-center border border-accent-400 border-dashed bg-transparent active:bg-accent-200 sm:hover:bg-accent-200 hover:text-foreground transition-colors rounded p-12'
                        >
                            New Template Group
                        </button>
                    )}
                </div>
            )}
        </NodeContentBlock>
    )
}

export default NodeTemplatesContainer