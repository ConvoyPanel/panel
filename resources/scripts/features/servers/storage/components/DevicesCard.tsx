import {
    type ServerStorage,
    hasBootableDevice,
    orderDevices,
    storageQueries,
    updateBootOrder,
    useStorage,
} from '@/features/servers/storage/api.ts'
import DeviceRow, {
    DeviceRowOverlay,
    deviceRowId,
} from '@/features/servers/storage/components/DeviceRow.tsx'
import { getApiErrorMessage } from '@/utils/http.ts'
import {
    type CollisionDetection,
    DndContext,
    type DragEndEvent,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core'
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { IconAlertTriangle, IconDatabase } from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { toast } from '@/components/ui/Toast'

/**
 * An unsaved boot order, plus the server's order at the moment editing started.
 *
 * Holding the baseline is what lets a background refetch land without either
 * silently overwriting the edit (the old `useEffect` sync) or silently hiding
 * that the server moved underneath it.
 */
interface Draft {
    order: string[]
    base: string[]
}

const sameOrder = (a: string[], b: string[]) =>
    a.length === b.length && a.every((value, index) => value === b[index])

/**
 * The column widths of the row being dragged, so the floating copy keeps the
 * table's proportions once it is out of the table.
 *
 * A `<tr>` on its own has no columns to size against, so the overlay carries a
 * one-row table of its own and this measurement is what makes it line up with
 * the one it left.
 */
const measureColumns = (row: HTMLElement | null) =>
    row
        ? [...row.querySelectorAll('td')].map(
              c => c.getBoundingClientRect().width
          )
        : []

interface Props {
    uuid: string
}

const DevicesCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data, isLoading, isError, refetch } = useStorage(uuid)

    const [draft, setDraft] = useState<Draft | null>(null)
    const [dragging, setDragging] = useState<{
        active: string | null
        over: string | null
        columns: number[]
    }>({ active: null, over: null, columns: [] })

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    )

    const devices = data?.devices ?? []
    const saved = data?.bootOrder ?? []

    // Devices can be detached elsewhere while an edit is open; a draft naming
    // one that no longer exists would save a boot order PVE cannot honour.
    const order = draft
        ? draft.order.filter(name => devices.some(d => d.interface === name))
        : saved

    const isDirty = draft !== null && !sameOrder(order, saved)
    // Only worth saying while there is something unsaved to lose: a draft that
    // has caught up with the server has nothing to conflict with.
    const hasConflict =
        draft !== null && isDirty && !sameOrder(draft.base, saved)

    const edit = (next: string[]) =>
        setDraft(current => ({ order: next, base: current?.base ?? saved }))

    const { mutate: save, isPending } = useMutation({
        mutationFn: () => updateBootOrder(uuid, order),
        onSuccess: () => {
            // Write the saved order into the cache before dropping the draft.
            // Dropping it first would fall back to the server's copy, which is
            // still the pre-save order until the refetch lands -- the rows
            // would visibly snap back and then forward again.
            queryClient.setQueryData<ServerStorage>(
                storageQueries.all(uuid),
                previous =>
                    previous ? { ...previous, bootOrder: order } : previous
            )
            setDraft(null)
            queryClient.invalidateQueries({
                queryKey: storageQueries.all(uuid),
            })
            toast.add({ title: 'Boot order updated', type: 'success' })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to update the boot order'),
                type: 'error',
            }),
    })

    /**
     * Only the devices in the boot order can be dropped on.
     *
     * `useSortable`'s `disabled` stops a row being *dragged*, not being a drop
     * target -- the switched-off rows still register, and dragging over one
     * hands the sort strategy an index it has no item for. It answers by
     * dropping every row's transform, which on screen is the dragged row
     * snapping home mid-gesture while the pointer is still down.
     */
    const collisionDetection: CollisionDetection = args =>
        closestCenter({
            ...args,
            droppableContainers: args.droppableContainers.filter(container =>
                order.includes(String(container.id))
            ),
        })

    const onDragEnd = ({ active, over, delta }: DragEndEvent) => {
        setDragging({ active: null, over: null, columns: [] })

        const from = order.indexOf(String(active.id))

        if (from === -1) return

        // Only the devices in the boot order are drop targets, so a drop past
        // the last of them -- onto a switched-off device, or off the rows
        // entirely -- resolves to nothing. Reverting the drag there reads as the
        // handle being broken, so it lands at whichever end it was headed for.
        const over_ = over ? order.indexOf(String(over.id)) : -1
        const to = over_ === -1 ? (delta.y > 0 ? order.length - 1 : 0) : over_

        if (to === from) return

        edit(arrayMove(order, from, to))
    }

    const onToggleBoot = (interfaceName: string, boots: boolean) =>
        edit(
            boots
                ? [...order, interfaceName]
                : order.filter(name => name !== interfaceName)
        )

    const rows = orderDevices(devices, order)

    const activeDevice = dragging.active
        ? devices.find(device => device.interface === dragging.active)
        : undefined

    // Ranks, however, read from where the rows have *moved to*. Numbering them
    // by the saved order while they sit somewhere else labels every one of them
    // wrong until the drop.
    const previewOrder = (() => {
        if (!dragging.active || !dragging.over) return order

        const from = order.indexOf(dragging.active)
        const to = order.indexOf(dragging.over)

        return from === -1 || to === -1 ? order : arrayMove(order, from, to)
    })()
    const isBootless = order.length > 0 && !hasBootableDevice(devices, order)

    return (
        <Card>
            <CardHeader>
                <CardTitle>Devices</CardTitle>
                <CardDescription>
                    Everything attached to this server. The firmware tries the
                    switched-on devices from the top down — drag to change the
                    order.
                </CardDescription>
            </CardHeader>

            <CardContent className={'flex-1 px-0'}>
                {isError && !data ? (
                    <div className={'px-4'}>
                        <CollectionErrorState onRetry={refetch} />
                    </div>
                ) : isLoading ? (
                    <Skeleton className={'mx-4 h-40'} />
                ) : rows.length === 0 ? (
                    <SimpleEmptyState
                        icon={IconDatabase}
                        title={'No devices attached'}
                        description={
                            'This server has no disks or drives on it yet.'
                        }
                    />
                ) : (
                    <div className={'flex flex-col gap-3'}>
                        {order.length === 0 && (
                            <Alert
                                variant={'destructive'}
                                className={'mx-4 w-auto'}
                            >
                                <IconAlertTriangle className={'size-4'} />
                                <AlertDescription>
                                    Nothing is switched on, so this server has
                                    nothing to boot from and will stop at its
                                    firmware.
                                </AlertDescription>
                            </Alert>
                        )}

                        {isBootless && (
                            <Alert
                                variant={'destructive'}
                                className={'mx-4 w-auto'}
                            >
                                <IconAlertTriangle className={'size-4'} />
                                <AlertDescription>
                                    Only the cloud-init drive is switched on. It
                                    holds no operating system, so this server
                                    will stop at its firmware — switch on a disk
                                    to boot from.
                                </AlertDescription>
                            </Alert>
                        )}

                        {hasConflict && (
                            <Alert className={'mx-4 w-auto'}>
                                <IconAlertTriangle className={'size-4'} />
                                <AlertDescription>
                                    The boot order changed elsewhere while you
                                    were editing. Your unsaved order is still
                                    below; saving replaces the newer one.
                                </AlertDescription>
                            </Alert>
                        )}

                        <DndContext
                            sensors={sensors}
                            collisionDetection={collisionDetection}
                            // The page may scroll if the row is dragged past
                            // the viewport; the table's own overflow container
                            // may not. Scrolling the list sideways out from
                            // under the pointer is the thing that makes a drag
                            // feel trapped in its box.
                            autoScroll={{
                                canScroll: element =>
                                    element === document.scrollingElement ||
                                    element === document.documentElement,
                            }}
                            onDragStart={({ active }) =>
                                setDragging({
                                    active: String(active.id),
                                    over: String(active.id),
                                    columns: measureColumns(
                                        document.getElementById(
                                            deviceRowId(String(active.id))
                                        )
                                    ),
                                })
                            }
                            onDragOver={({ active, over }) =>
                                setDragging(current => ({
                                    ...current,
                                    active: String(active.id),
                                    over: over ? String(over.id) : null,
                                }))
                            }
                            onDragCancel={() =>
                                setDragging({
                                    active: null,
                                    over: null,
                                    columns: [],
                                })
                            }
                            onDragEnd={onDragEnd}
                        >
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className={'w-9 pl-4'}>
                                            <span className={'sr-only'}>
                                                Reorder
                                            </span>
                                        </TableHead>
                                        <TableHead className={'w-8'}>
                                            #
                                        </TableHead>
                                        <TableHead className={'w-12'}>
                                            Boot
                                        </TableHead>
                                        <TableHead className={'w-44'}>
                                            Device
                                        </TableHead>
                                        <TableHead>Backing</TableHead>
                                        <TableHead
                                            className={'w-24 text-right'}
                                        >
                                            Size
                                        </TableHead>
                                        <TableHead className={'w-56'}>
                                            Options
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                {/* One body, and one flat list of rows inside
                                    it. Splitting the booting rows from the
                                    skipped ones put the two groups under
                                    different parents, so switching a device on
                                    moved its row across that boundary -- which
                                    to React is an unmount and a fresh mount.
                                    The new switch renders already-checked and
                                    has nothing to animate from, which is the
                                    thumb snapping across with no travel.

                                    Only `order` is handed to SortableContext,
                                    so the switched-off rows still take no part
                                    in the sort; `collisionDetection` above is
                                    what keeps them from being dropped on. */}
                                <TableBody>
                                    <SortableContext
                                        items={order}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {rows.map(device => {
                                            const position =
                                                previewOrder.indexOf(
                                                    device.interface
                                                )

                                            return (
                                                <DeviceRow
                                                    key={device.interface}
                                                    device={device}
                                                    rank={
                                                        position === -1
                                                            ? null
                                                            : position + 1
                                                    }
                                                    isMutating={isPending}
                                                    onToggleBoot={boots =>
                                                        onToggleBoot(
                                                            device.interface,
                                                            boots
                                                        )
                                                    }
                                                />
                                            )
                                        })}
                                    </SortableContext>
                                </TableBody>
                            </Table>

                            {/* The row travels in here rather than in the table,
                                so it follows the pointer anywhere on the page
                                instead of being held to the rows it came from.
                                It needs a table of its own -- a lone `tr` has no
                                columns to size against -- with the widths
                                measured off the row it was lifted from. */}
                            <DragOverlay>
                                {activeDevice && (
                                    <table
                                        className={
                                            'bg-card ring-foreground/10 [table-layout:fixed] cursor-grabbing overflow-hidden rounded-lg text-sm shadow-lg ring-1'
                                        }
                                    >
                                        <colgroup>
                                            {dragging.columns.map(
                                                (width, index) => (
                                                    <col
                                                        key={index}
                                                        style={{ width }}
                                                    />
                                                )
                                            )}
                                        </colgroup>
                                        <tbody>
                                            <DeviceRowOverlay
                                                device={activeDevice}
                                                rank={
                                                    previewOrder.indexOf(
                                                        activeDevice.interface
                                                    ) + 1
                                                }
                                                isMutating={isPending}
                                                onToggleBoot={() => {}}
                                            />
                                        </tbody>
                                    </table>
                                )}
                            </DragOverlay>
                        </DndContext>
                    </div>
                )}
            </CardContent>

            {/* Always mounted, so touching a switch cannot resize the card
                underneath the pointer that touched it. */}
            <CardFooter className={'text-muted-foreground gap-3 text-xs'}>
                <span className={'flex-1'}>
                    {isDirty
                        ? 'Takes effect the next time this server powers on.'
                        : 'Boot order is applied by the firmware at power on.'}
                </span>
                <Button
                    variant={'outline'}
                    size={'sm'}
                    disabled={!isDirty || isPending}
                    onClick={() => setDraft(null)}
                >
                    Reset
                </Button>
                <Button
                    size={'sm'}
                    disabled={!isDirty}
                    loading={isPending}
                    onClick={() => save()}
                >
                    Save changes
                </Button>
            </CardFooter>
        </Card>
    )
}

export default DevicesCard
