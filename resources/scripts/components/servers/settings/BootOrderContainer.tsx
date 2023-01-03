import { ServerContext } from '@/state/server'
import useSWR from 'swr'
import getBootOrder from '@/api/server/settings/getBootOrder'
import { useEffect, useMemo, useState } from 'react'
import { Disk } from '@/api/server/useServerDetails'
import useNotify from '@/util/useNotify'
import useFlash from '@/util/useFlash'
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

const BootOrderContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data } = useSWR(['server:settings:hardware:boot-order', uuid], () => getBootOrder(uuid), {
        revalidateOnFocus: false,
        revalidateOnMount: true,
        revalidateOnReconnect: false,
        refreshWhenOffline: false,
        refreshWhenHidden: false,
        refreshInterval: 0,
    })

    const [bootOrder, setBootOrder] = useState<Disk[]>([])
    const bootOrderIds = useMemo(() => bootOrder.map(disk => disk.name), [bootOrder])
    const [unusedDevices, setUnusedDevices] = useState<Disk[]>([])
    const notify = useNotify()
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    useEffect(() => {
        setBootOrder(data?.bootOrder ?? [])
        setUnusedDevices(data?.unusedDevices ?? [])

        console.log(data)
    }, [data])

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event

        if (over && active.id !== over.id) {
            setBootOrder(items => {
                const oldIndex = items.findIndex(disk => disk.name === (active.id as string))
                const newIndex = items.findIndex(disk => disk.name === (over.id as string))

                return arrayMove(items, oldIndex, newIndex)
            })
        }
    }

    const removeDevice = (device: Disk) => {
        setBootOrder(bootOrder.filter(disk => disk.name !== device.name))
        setUnusedDevices([...unusedDevices, device])
    }

    const addDevice = (device: Disk) => {
        setBootOrder([...bootOrder, device])
        setUnusedDevices(unusedDevices.filter(disk => disk.name !== device.name))
    }

    const [loading, setLoading] = useState(false)

    const submit = () => {
        setLoading(true)
        clearFlashes('server:settings:hardware:boot-order')

        updateBootOrder(
            uuid,
            bootOrder.map(disk => disk.name)
        )
            .then(() => {
                setLoading(false)
                notify({
                    title: 'Updated',
                    message: 'Updated boot order',
                    color: 'green',
                })
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'server:settings:hardware:boot-order', error })
                setLoading(false)
            })
    }

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Device Configuration</FormCard.Title>
                <FlashMessageRender className='mt-3' byKey='server:settings:hardware:boot-order' />
                <div className='mt-3'>
                    <h5 className='text-accent-600 text-sm'>Current Boot Order (the highest will be used first)</h5>
                    <div className='flex flex-col space-y-3 mt-3 mb-5'>
                        {bootOrder.length === 0 && (
                            <MessageBox title='Warning' type='warning'>
                                No boot device has been configured. Your VM will not start.
                            </MessageBox>
                        )}
                        <DndContext
                            onDragEnd={handleDragEnd}
                            modifiers={[restrictToVerticalAxis, restrictToWindowEdges]}
                        >
                            <SortableContext items={bootOrderIds} strategy={verticalListSortingStrategy}>
                                {bootOrder.map(disk => (
                                    <SortableItem
                                        handle
                                        className='select-none flex items-center space-x-3 p-2 border border-accent-200 sm:dark:hover:border-foreground dark:active:border-foreground dark:shadow-none dark:hover:shadow-none bg-background shadow-light hover:shadow-lg rounded'
                                        key={disk.name}
                                        id={disk.name}
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
                                                    {disk.displayName ? (
                                                        <div className={'flex flex-col'}>
                                                            <div className={'flex space-x-3'}>
                                                                <span className='text-foreground'>
                                                                    {disk.displayName}
                                                                </span>
                                                                {disk.type === 'media' ? <Badge>Media</Badge> : null}
                                                            </div>

                                                            <span className='description-small'>
                                                                {disk.name}, {bytesToString(disk.size)}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <div className={'flex flex-col'}>
                                                            <div className={'flex space-x-3'}>
                                                                <span className='text-foreground'>{disk.name}</span>
                                                                {disk.type === 'media' ? <Badge>Media</Badge> : null}
                                                            </div>

                                                            <span className='description-small'>
                                                                {bytesToString(disk.size)}
                                                            </span>
                                                        </div>
                                                    )}

                                                    <button
                                                        onClick={() => removeDevice(disk)}
                                                        className='bg-transparent p-1'
                                                    >
                                                        <XMarkIcon className='h-6 w-6 text-accent-500' />
                                                    </button>
                                                </div>
                                            </>
                                        )}
                                    </SortableItem>
                                ))}
                            </SortableContext>
                        </DndContext>
                    </div>
                    <h5 className='text-accent-600 text-sm'>Unused Devices</h5>
                    <div className='flex flex-col space-y-3 mt-3'>
                        {unusedDevices.length === 0 && (
                            <p className='text-sm text-center'>There are no unused devices</p>
                        )}
                        {unusedDevices.map(device => (
                            <div key={device.name} className='py-2 pl-4 pr-2 border border-accent-200 rounded'>
                                <div className='flex justify-between w-full items-center'>
                                    {device.displayName ? (
                                        <div className={'flex flex-col'}>
                                            <div className={'flex space-x-3'}>
                                                <span className='text-foreground'>{device.displayName}</span>
                                                {device.type === 'media' ? <Badge>Media</Badge> : null}
                                            </div>

                                            <span className='description-small'>
                                                {device.name}, {bytesToString(device.size)}
                                            </span>
                                        </div>
                                    ) : (
                                        <div className={'flex flex-col'}>
                                            <div className={'flex space-x-3'}>
                                                <span className='text-foreground'>{device.name}</span>
                                                {device.type === 'media' ? <Badge>Media</Badge> : null}
                                            </div>

                                            <span className='description-small'>{bytesToString(device.size)}</span>
                                        </div>
                                    )}
                                    <button onClick={() => addDevice(device)} className='bg-transparent p-1'>
                                        <PlusIcon className='h-6 w-6 text-accent-500' />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button loading={loading} onClick={submit} variant='filled' color='success' size='sm'>
                    Save
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

export default BootOrderContainer
