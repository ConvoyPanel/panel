// @ts-expect-error
import DragVerticalIcon from '@/assets/images/icons/drag-vertical.svg'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { useFlashKey } from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { DndContext, DragEndEvent } from '@dnd-kit/core'
import {
    restrictToVerticalAxis,
    restrictToWindowEdges,
} from '@dnd-kit/modifiers'
import {
    SortableContext,
    arrayMove,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { PlusIcon, XMarkIcon } from '@heroicons/react/20/solid'
import { Badge } from '@mantine/core'
import { updateNotification } from '@mantine/notifications'
import { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

import { httpErrorToHuman } from '@/api/http'
import { BootOrderSettings } from '@/api/server/settings/getBootOrder'
import updateBootOrder from '@/api/server/settings/updateBootOrder'
import useBootOrderSWR from '@/api/server/settings/useBootOrderSWR'
import { Disk } from '@/api/server/useServerDetails'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'
import Translate from '@/components/elements/Translate'
import SortableItem, {
    ChildrenPropsWithHandle,
} from '@/components/elements/dnd/SortableItem'


const BootOrderCard = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data, mutate } = useBootOrderSWR(uuid)
    const { t } = useTranslation('server.settings')
    const { t: tStrings } = useTranslation('strings')

    const notify = useNotify()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${uuid}.settings.hardware.boot-order`
    )

    const updateOrder = (disks: string[]) => {
        clearFlashes()

        notify({
            id: `servers.${uuid}.settings.hardware.boot-order`,
            loading: true,
            message: tStrings('saving'),
            autoClose: false,
            disallowClose: true,
        })

        updateBootOrder(uuid, disks)
            .then(() => {
                updateNotification({
                    id: `servers.${uuid}.settings.hardware.boot-order`,
                    message: tStrings('saved'),
                    autoClose: 1000,
                })
            })
            .catch(error => {
                updateNotification({
                    id: `servers.${uuid}.settings.hardware.boot-order`,
                    color: 'red',
                    message: httpErrorToHuman(error),
                    autoClose: 5000,
                })
                clearAndAddHttpError(error)
            })
    }

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        if (over && active.id !== over.id) {
            mutate(data => {
                const oldIndex = data!.bootOrder.findIndex(
                    disk => disk.interface === (active.id as string)
                )
                const newIndex = data!.bootOrder.findIndex(
                    disk => disk.interface === (over.id as string)
                )

                const bootOrder = arrayMove(data!.bootOrder, oldIndex, newIndex)

                updateOrder(bootOrder.map(disk => disk.interface))

                return {
                    ...data,
                    bootOrder,
                } as BootOrderSettings
            }, false)
        }
    }

    const removeDisk = (device: Disk) => {
        mutate(data => {
            const bootOrder = data!.bootOrder.filter(
                disk => disk.interface !== device.interface
            )
            const unusedDevices = [...data!.unusedDevices, device]

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
            const bootOrder = [...data!.bootOrder, device]
            const unusedDevices = data!.unusedDevices.filter(
                disk => disk.interface !== device.interface
            )

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
                <FormCard.Title>{t('device_config.title')}</FormCard.Title>
                <FlashMessageRender
                    className='mt-3'
                    byKey={`servers.${uuid}.settings.hardware.boot-order`}
                />
                <div className='mt-3'>
                    <h5 className='text-accent-600 text-sm'>
                        {t('device_config.current')}
                    </h5>
                    <div className='flex flex-col space-y-3 mt-3 mb-5'>
                        {data?.bootOrder.length === 0 && (
                            <MessageBox title='Warning' type='warning'>
                                {t('device_config.no_boot_device_warning')}
                            </MessageBox>
                        )}
                        <DndContext
                            onDragEnd={handleDragEnd}
                            modifiers={[
                                restrictToVerticalAxis,
                                restrictToWindowEdges,
                            ]}
                        >
                            <SortableContext
                                items={
                                    data?.bootOrder.map(
                                        disk => disk.interface
                                    ) ?? []
                                }
                                strategy={verticalListSortingStrategy}
                            >
                                {data?.bootOrder.map(disk => (
                                    <DraggableDiskRow
                                        key={disk.interface}
                                        disk={disk}
                                        action={
                                            <button
                                                onClick={() => removeDisk(disk)}
                                                className='bg-transparent p-1'
                                            >
                                                <XMarkIcon className='h-6 w-6 text-accent-500' />
                                            </button>
                                        }
                                    />
                                ))}
                            </SortableContext>
                        </DndContext>
                    </div>
                    <h5 className='text-accent-600 text-sm'>
                        {t('device_config.unused')}
                    </h5>
                    <div className='flex flex-col space-y-3 mt-3'>
                        {data?.unusedDevices.length === 0 && (
                            <p className='text-sm text-center'>
                                {t('device_config.unused_empty')}
                            </p>
                        )}
                        {data?.unusedDevices.map(device => (
                            <StaticDiskRow
                                key={device.interface}
                                disk={device}
                                action={
                                    <button
                                        onClick={() => addDisk(device)}
                                        className='bg-transparent p-1'
                                    >
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

                <span className='description-small'>
                    {getDescription(disk)}
                </span>
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
                <button
                    className='bg-transparent touch-none p-1'
                    {...attributes}
                    {...listeners}
                >
                    <img
                        src={DragVerticalIcon}
                        className='h-6 w-6 dark:invert'
                        alt='Drag button icon'
                    />
                </button>
                <div className='flex justify-between w-full items-center'>
                    <div className={'flex flex-col'}>
                        <div className={'flex space-x-3'}>
                            <span className='text-foreground'>
                                {getName(disk)}
                            </span>
                            {getBadge(disk)}
                        </div>

                        <span className='description-small'>
                            {getDescription(disk)}
                        </span>
                    </div>

                    {action}
                </div>
            </>
        )}
    </SortableItem>
)

const getName = (disk: Disk) => {
    if (disk.isPrimaryDisk) {
        return <Translate ns={'strings'} i18nKey={'primary_disk'} />
    }
    if (disk.isMedia) {
        return disk.mediaName
    }
    return disk.interface
}

const getBadge = (disk: Disk) => {
    if (disk.isPrimaryDisk) {
        return (
            <Badge>
                <Translate ns={'strings'} i18nKey={'primary'} />
            </Badge>
        )
    }

    if (disk.isMedia) {
        return (
            <Badge>
                <Translate ns={'strings'} i18nKey={'media'} />
            </Badge>
        )
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