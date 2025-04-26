import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { NodeStorage } from '@/types/storage.ts'
import {
    DndContext,
    DragEndEvent,
    DragOverlay,
    DragStartEvent,
    closestCenter,
} from '@dnd-kit/core'
import {
    SortableContext,
    arrayMove,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { IconDatabase } from '@tabler/icons-react'
import { useMemo, useState } from 'react'
import { toast } from 'sonner'

import updateBackupOrder from '@/api/admin/nodes/storages/updateBackupOrder.ts'
import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

import SortableStorageCard from '@/components/interfaces/Admin/Node/Storages/SortableStorageCard.tsx'

import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
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
    // Ensure useStoragesSWR returns the mutate function correctly
    const { data: allStorages, mutate, isLoading } = useStoragesSWR()
    const [draggingStorage, setDraggingStorage] = useState<NodeStorage | null>(
        null
    )

    // Memoize the list of storages filtered and sorted for backup load balancing
    const backupStorages = useMemo(() => {
        if (!allStorages) {
            return []
        }
        // Filter for storages used for backups and sort them by their current backupOrder
        return [...allStorages]
            .filter(storage => storage.storesBackups)
            .sort(
                (a, b) =>
                    (a.backupOrder ?? Infinity) - (b.backupOrder ?? Infinity)
            ) // Handle potential null orders
    }, [allStorages])

    // Memoize the IDs of the backup storages for SortableContext
    const backupStorageIds = useMemo(
        () => backupStorages.map(s => s.id),
        [backupStorages]
    )

    const handleDragEnd = async (event: DragEndEvent) => {
        setDraggingStorage(null) // Clear the dragging state visual
        const { active, over } = event

        // Check if the drag ended over a valid drop target and the item moved
        if (over && active.id !== over.id) {
            // Find the original indices in the *filtered* backupStorages array
            const oldIndex = backupStorages.findIndex(s => s.id === active.id)
            const newIndex = backupStorages.findIndex(s => s.id === over.id)

            // Ensure both items were found in the backup list
            if (oldIndex === -1 || newIndex === -1) {
                console.error(
                    'Dragged or target item not found in backup storages'
                )
                return // Should not happen if IDs are correct
            }

            // 1. --- Calculate the new order for backup storages ---
            const reorderedBackupStorages = arrayMove(
                backupStorages,
                oldIndex,
                newIndex
            )

            // 2. --- Prepare the optimistic data for SWR ---
            // Map through the *original* full list of storages.
            // Update the backupOrder for those that are in the reorderedBackupStorages list.
            // Assign sequential order (1, 2, 3...) based on the new array position.
            const optimisticData = allStorages?.map(storage => {
                const indexInReordered = reorderedBackupStorages.findIndex(
                    bs => bs.id === storage.id
                )
                if (indexInReordered !== -1) {
                    // This storage is a backup storage, update its order
                    return {
                        ...storage,
                        backupOrder: indexInReordered + 1, // Assign order 1-based index
                    }
                } else {
                    // Not a backup storage, or not in the list for some reason, keep original
                    // Note: non-backup storages should ideally have null backupOrder from backend
                    return storage
                }
            })

            // 3. --- Extract IDs *only* for backup storages in the new order ---
            const idsToSend = reorderedBackupStorages.map(s => s.id)

            // --- Manually handle loading toast ---
            const toastId = toast.loading('Saving backup order...') // Show loading toast immediately

            // 4. --- Perform the optimistic update using SWR mutate ---
            try {
                // Apply optimistic update immediately
                await mutate(
                    async () => {
                        // This function is called to perform the actual API request
                        await updateBackupOrder(nodeId, idsToSend)
                        // Return the data that should be in the cache after successful update.
                        // This should match optimisticData if the backend assigns orders correctly.
                        // If the backend returns the updated list, you could return that instead.
                        return optimisticData
                    },
                    {
                        // Configuration for optimistic update:
                        optimisticData: optimisticData, // The data to show immediately
                        rollbackOnError: true, // Revert UI if updateBackupOrder throws an error
                        populateCache: true, // Update the cache with the result of the async function
                        revalidate: false, // Don't revalidate immediately after mutation (API call handles it)
                    }
                )
                // Update the toast to success
                toast.success('Saved changes', {
                    id: toastId,
                })
            } catch (error) {
                console.error('Failed to update backup order:', error)
                // Update the toast to error
                toast.error('Failed to save changes', {
                    id: toastId,
                })
                // SWR handles the UI rollback automatically due to rollbackOnError: true
            }
        }
    }

    const handleDragStart = (event: DragStartEvent) => {
        // Find the storage being dragged from the *backup* list
        const dragged = backupStorages.find(s => s.id === event.active.id)
        setDraggingStorage(dragged || null)
    }

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant={'outline'} size={'sm'}>
                    Load balancer
                </Button>
            </SheetTrigger>
            <SheetContent side={'right'} className='flex flex-col'>
                {' '}
                {/* Ensure flex column layout */}
                <SheetHeader>
                    <SheetTitle>Load Balancer</SheetTitle>
                    <SheetDescription>
                        Drag and drop to reorder the storages used for backups.
                        The top storage is used first. Only storages marked
                        'stores backups' are shown here.
                    </SheetDescription>
                </SheetHeader>
                <div className='flex-grow overflow-y-auto py-4'>
                    {' '}
                    {/* Make content scrollable */}
                    {isLoading ? (
                        <div className={'flex flex-col gap-2 px-4 @md:gap-4'}>
                            {Array.from({ length: 4 }).map((_, index) => (
                                <Skeleton
                                    key={index}
                                    className={'h-24 w-full'}
                                />
                            ))}
                        </div>
                    ) : backupStorages.length === 0 ? (
                        <Card>
                            <CardContent className='pt-6'>
                                {' '}
                                {/* Add padding top */}
                                <SimpleEmptyState
                                    icon={IconDatabase}
                                    title={'No Backup Storages'}
                                    description={
                                        'No storages on this node are configured to store backups, or no storages exist yet.'
                                    }
                                />
                            </CardContent>
                        </Card>
                    ) : (
                        <DndContext
                            collisionDetection={closestCenter} // Or another strategy if needed
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                        >
                            {/* Use the memoized backupStorageIds */}
                            <SortableContext
                                items={backupStorageIds}
                                strategy={verticalListSortingStrategy}
                            >
                                <ol className={'flex flex-col gap-3'}>
                                    {' '}
                                    {/* Add padding */}
                                    {/* Map over the state derived from SWR data */}
                                    {backupStorages.map(storage => (
                                        <SortableStorageCard
                                            key={storage.id}
                                            storage={storage}
                                        />
                                    ))}
                                </ol>
                            </SortableContext>
                            <DragOverlay>
                                {draggingStorage ? (
                                    <SortableStorageCard
                                        storage={draggingStorage}
                                        isOverlay // Ensure your card handles this prop for styling
                                    />
                                ) : null}
                            </DragOverlay>
                        </DndContext>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    )
}

export default LoadBalancerSidebar
