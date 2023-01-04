import { TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import useTemplatesGroupSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import Button from '@/components/elements/Button'
import Card from '@/components/elements/Card'
import Spinner from '@/components/elements/Spinner'
import { NodeContext } from '@/state/admin/node'
import { useEffect, useState } from 'react'
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
import { arrayMove, SortableContext } from '@dnd-kit/sortable'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
import { restrictToWindowEdges } from '@dnd-kit/modifiers'
import reorderTemplateGroups from '@/api/admin/nodes/templateGroups/reorderTemplateGroups'
import useNotify from '@/util/useNotify'
import { updateNotification } from '@mantine/notifications'
import TemplateGroupCard from '@/components/admin/nodes/templates/TemplateGroupCard'
import EditTemplateGroupModal from '@/components/admin/nodes/templates/EditTemplateGroupModal'
import useFlash from '@/util/useFlash'
import { httpErrorToHuman } from '@/api/http'

const NodeTemplatesContainer = () => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { data, mutate } = useTemplatesGroupSWR(nodeId, [])
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

        reorderTemplateGroups(nodeId, groups)
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
                clearAndAddHttpError({ key: 'admin:node:template-groups', error })
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
                newGroups.forEach((group, index) => (group.orderColumn = index + 1))

                updateGroupOrder(newGroups.map(group => group.id))

                return newGroups
            }, false)
        }
    }

    return (
        <NodeContentBlock title='Templates' showFlashKey='admin:node:template-groups'>
            <EditTemplateGroupModal open={showCreateModal} onClose={() => setShowCreateModal(false)} />
            <div className='flex justify-end items-center mb-3'>
                <Button onClick={() => setShowCreateModal(true)} disabled={data!.length >= 50} variant='filled'>
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
                                <TemplateGroupCard group={group} key={group.id} />
                            ))}
                        </SortableContext>
                        <DragOverlay>{activeId && <TemplateGroupCard group={activeId} className='z-20' />}</DragOverlay>
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
