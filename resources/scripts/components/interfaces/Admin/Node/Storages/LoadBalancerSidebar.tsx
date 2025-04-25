import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { NodeStorage } from '@/types/storage.ts'
import { DndContext, DragEndEvent, DragOverlay } from '@dnd-kit/core'
import { SortableContext } from '@dnd-kit/sortable'
import { IconDatabase } from '@tabler/icons-react'
import { useMemo, useState } from 'react'
import { toast } from 'sonner'

import updateBackupOrder from '@/api/admin/nodes/storages/updateBackupOrder.ts'
import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

import SortableStorageCard from '@/components/interfaces/Admin/Node/Storages/SortableStorageCard.tsx'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/Sheet'
import Skeleton from '@/components/ui/Skeleton.tsx'

const LoadBalancerSidebar = () => {
    const { nodeId } = StorageRoute.useParams()
    const { data: storages, mutate, isLoading } = useStoragesSWR()
    const [draggingStorage, setDraggingStorage] = useState<NodeStorage | null>()

    const sortedStorages = useMemo(() => {
        if (!storages) {
            return []
        }

        return [...storages].sort((a, b) => {
            return a.backupOrder - b.backupOrder
        })
    }, [storages])

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        setDraggingStorage(null)

        if (over && active.id !== over.id) {
            mutate(storages => {
                if (!storages) return storages

                const draggedStorage = storages.find(s => s.id === active.id)
                const targetStorage = storages.find(s => s.id === over.id)

                if (!draggedStorage || !targetStorage) return storages

                // Swap backupOrder values
                const updatedStorages = storages.map(storage => {
                    if (storage.id === active.id) {
                        return {
                            ...storage,
                            backupOrder: targetStorage.backupOrder,
                        }
                    }
                    if (storage.id === over.id) {
                        return {
                            ...storage,
                            backupOrder: draggedStorage.backupOrder,
                        }
                    }
                    return storage
                })

                toast.promise(
                    updateBackupOrder(
                        nodeId,
                        updatedStorages.map(s => s.id)
                    ),
                    {
                        loading: 'Saving changes...',
                        success: 'Changes saved successfully',
                        error: 'Save failed',
                    }
                )

                return updatedStorages
            }, false)
        }
    }

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant={'outline'} size={'sm'}>
                    Load balancer
                </Button>
            </SheetTrigger>
            <SheetContent side={'right'}>
                <SheetHeader>
                    <SheetTitle>Load Balancer</SheetTitle>
                    <SheetDescription>
                        Manage the order in which storages are used by the load
                        balancer for storing KVM backups. The uppermost storage
                        will be used first, followed by the second storage, and
                        so on.
                    </SheetDescription>
                </SheetHeader>
                {isLoading ? (
                    <div className={'flex flex-col gap-2 @md:gap-4'}>
                        {Array.from({ length: 4 }).map((_, index) => (
                            <Skeleton key={index} className={'h-24'} />
                        ))}
                    </div>
                ) : storages?.length === 0 ? (
                    <Card>
                        <CardHeader className={'pb-0'} />
                        <CardContent>
                            <SimpleEmptyState
                                icon={IconDatabase}
                                title={'Storages'}
                                description={
                                    'No storages have been created for this node yet. Add a storage to enable server deployments and resource management.'
                                }
                            />
                        </CardContent>
                    </Card>
                ) : (
                    <DndContext
                        onDragStart={e =>
                            setDraggingStorage(
                                storages!.find(
                                    storage => storage.id === e.active.id
                                )
                            )
                        }
                        onDragEnd={handleDragEnd}
                    >
                        <ol className={'flex h-full flex-col gap-3 py-3'}>
                            <SortableContext
                                items={sortedStorages.map(
                                    storage => storage.id
                                )}
                            >
                                {sortedStorages.map(storage => (
                                    <SortableStorageCard
                                        key={storage.id}
                                        storage={storage}
                                    />
                                ))}
                            </SortableContext>
                            <DragOverlay>
                                {draggingStorage && (
                                    <SortableStorageCard
                                        storage={draggingStorage}
                                        isOverlay
                                    />
                                )}
                            </DragOverlay>
                        </ol>
                    </DndContext>
                )}
            </SheetContent>
        </Sheet>
    )
}

export default LoadBalancerSidebar
