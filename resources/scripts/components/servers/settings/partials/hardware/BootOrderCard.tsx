import { ServerContext } from '@/state/server'
import useSWR from 'swr'
import getBootOrder, { BootOrderSettings } from '@/api/server/settings/getBootOrder'
import { ReactNode, useEffect, useMemo, useState } from 'react'
import { Disk } from '@/api/server/useServerDetails'
import useNotify from '@/util/useNotify'
import useFlash, { useFlashKey } from '@/util/useFlash'
import { DndContext, DragEndEvent } from '@dnd-kit/core'
import { arrayMove, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable'
import updateBootOrder from '@/api/server/settings/updateBootOrder'
import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import { restrictToVerticalAxis, restrictToWindowEdges } from '@dnd-kit/modifiers'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
// @ts-expect-error
import DragVerticalIcon from '@/assets/images/icons/drag-vertical.svg'
import { Badge } from '@mantine/core'
import { bytesToString } from '@/util/helpers'
import { PlusIcon, XMarkIcon } from '@heroicons/react/20/solid'
import Button from '@/components/elements/Button'
import useBootOrderSWR from '@/api/server/settings/useBootOrderSWR'
import { updateNotification } from '@mantine/notifications'
import { httpErrorToHuman } from '@/api/http'

const BootOrderCard = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data, mutate } = useBootOrderSWR(uuid)

    const notify = useNotify()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('server:settings:hardware:boot-order')

    const updateOrder = (disks: string[]) => {
        clearFlashes()

        notify({
            id: 'admin:node:template-groups.reorder',
            loading: true,
            message: 'Saving changes...',
            autoClose: false,
            disallowClose: true,
        })

        updateBootOrder(uuid, disks)
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
                clearAndAddHttpError( error )
            })
    }

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        if (over && active.id !== over.id) {
            mutate(data => {
                const oldIndex = data!.bootOrder.findIndex(disk => disk.interface === (active.id as string))
                const newIndex = data!.bootOrder.findIndex(disk => disk.interface === (over.id as string))

                const bootOrder = arrayMove(data!.bootOrder, oldIndex, newIndex);

                updateOrder(bootOrder.map(disk => disk.interface))

                return {
                    ...data,
                    bootOrder
                } as BootOrderSettings
            }, false)
        }
    }

    const removeDisk = (device: Disk) => {
        mutate(data => {
            const bootOrder = data!.bootOrder.filter(disk => disk.interface !== device.interface)
            const unusedDevices = [ ...data!.unusedDevices, device ]

            updateOrder(bootOrder.map(disk => disk.interface))

            return {
                ...data,
                bootOrder,
                unusedDevices,
            } as BootOrderSettings
        }, false)
    }

    const addDisk = (device: Disk) => {
        mutate(data => {
            const bootOrder = [ ...data!.bootOrder, device ]
            const unusedDevices = data!.unusedDevices.filter(disk => disk.interface !== device.interface)

            updateOrder(bootOrder.map(disk => disk.interface))

            return {
                ...data,
                bootOrder,
                unusedDevices,
            } as BootOrderSettings
        }, false)
    }

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Device Configuration</FormCard.Title>
                <FlashMessageRender className='mt-3' byKey='server:settings:hardware:boot-order' />
                <div className='mt-3'>
                    <h5 className='text-accent-600 text-sm'>Current Boot Order (the highest will be used first)</h5>
                    <div className='flex flex-col space-y-3 mt-3 mb-5'>
                        {data?.bootOrder.length === 0 && (
                            <MessageBox title='Warning' type='warning'>
                                No boot device has been configured. Your VM will not start.
                            </MessageBox>
                        )}
                        <DndContext
                            onDragEnd={handleDragEnd}
                            modifiers={[restrictToVerticalAxis, restrictToWindowEdges]}
                        >
                            <SortableContext
                                items={data?.bootOrder.map(disk => disk.interface) ?? []}
                                strategy={verticalListSortingStrategy}
                            >
                                {data?.bootOrder.map(disk => (
                                    <DraggableDiskRow
                                        key={disk.interface}
                                        disk={disk}
                                        action={
                                            <button onClick={() => removeDisk(disk)} className='bg-transparent p-1'>
                                                <XMarkIcon className='h-6 w-6 text-accent-500' />
                                            </button>
                                        }
                                    />
                                ))}
                            </SortableContext>
                        </DndContext>
                    </div>
                    <h5 className='text-accent-600 text-sm'>Unused Devices</h5>
                    <div className='flex flex-col space-y-3 mt-3'>
                        {data?.unusedDevices.length === 0 && (
                            <p className='text-sm text-center'>There are no unused devices</p>
                        )}
                        {data?.unusedDevices.map(device => (
                            <StaticDiskRow
                                key={device.interface}
                                disk={device}
                                action={
                                    <button onClick={() => addDisk(device)} className='bg-transparent p-1'>
                                        <PlusIcon className='h-6 w-6 text-accent-500' />
                                    </button>
                                }
                            />
                        ))}
                    </div>
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

interface DiskRowProps {
    disk: Disk
    action: ReactNode
}

const StaticDiskRow = ({ disk, action }: DiskRowProps) => (
    <div className='py-2 pl-4 pr-2 border border-accent-200 rounded'>
        <div className='flex justify-between w-full items-center'>
            <div className={'flex flex-col'}>
                <div className={'flex space-x-3'}>
                    <span className='text-foreground'>{getName(disk)}</span>
                    {getBadge(disk)}
                </div>

                <span className='description-small'>{getDescription(disk)}</span>
            </div>
            {action}
        </div>
    </div>
)

const DraggableDiskRow = ({ disk, action }: DiskRowProps) => (
    <SortableItem
        handle
        className='select-none flex items-center space-x-3 p-2 border border-accent-200 sm:dark:hover:border-foreground dark:active:border-foreground dark:shadow-none dark:hover:shadow-none bg-background shadow-light hover:shadow-lg rounded'
        id={disk.interface}
    >
        {({ attributes, listeners }: ChildrenPropsWithHandle) => (
            <>
                <button className='bg-transparent touch-none p-1' {...attributes} {...listeners}>
                    <img src={DragVerticalIcon} className='h-6 w-6 dark:invert' alt='Drag button icon' />
                </button>
                <div className='flex justify-between w-full items-center'>
                    <div className={'flex flex-col'}>
                        <div className={'flex space-x-3'}>
                            <span className='text-foreground'>{getName(disk)}</span>
                            {getBadge(disk)}
                        </div>

                        <span className='description-small'>{getDescription(disk)}</span>
                    </div>

                    {action}
                </div>
            </>
        )}
    </SortableItem>
)

const getName = (disk: Disk) => {
    if (disk.isPrimaryDisk) {
        return 'Primary Disk'
    }
    if (disk.isMedia) {
        return disk.mediaName
    }
    return disk.interface
}

const getBadge = (disk: Disk) => {
    if (disk.isPrimaryDisk) {
        return <Badge>Primary</Badge>
    }

    if (disk.isMedia) {
        return <Badge>Media</Badge>
    }

    return null
}

const getDescription = (disk: Disk) => {
    if (disk.isPrimaryDisk || disk.isMedia) {
        return `${disk.interface}, ${bytesToString(disk.size)}`
    }

    return bytesToString(disk.size)
}

export default BootOrderCard
