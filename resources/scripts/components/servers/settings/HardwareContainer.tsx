import getBootOrder from '@/api/server/settings/getBootOrder'
import { Dd, Dt } from '@/components/dashboard/ServerCard'
import Button from '@/components/elements/Button'
import Display from '@/components/elements/displays/DisplayRow'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { useFormik } from 'formik'
import { useEffect, useMemo, useState } from 'react'
import useSWR from 'swr'
import * as yup from 'yup'
import { DndContext, DragEndEvent } from '@dnd-kit/core'
import { arrayMove, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
//@ts-ignore
import DragVerticalIcon from '@/assets/images/icons/drag-vertical.svg'

import { restrictToVerticalAxis, restrictToWindowEdges } from '@dnd-kit/modifiers'
import MessageBox from '@/components/elements/MessageBox'
import { PlusIcon, XMarkIcon } from '@heroicons/react/20/solid'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import updateBootOrder from '@/api/server/settings/updateBootOrder'
import { Disk } from '@/api/server/useServerDetails'

const HardwareContainer = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const notify = useNotify()

    const form = useFormik({
        initialValues: {
            name: server.name,
            hostname: server.hostname,
        },
        validationSchema: yup.object({
            name: yup.string().required('A name is required').max(40),
            hostname: yup
                .string()
                .matches(
                    /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
                    'Enter a valid hostname'
                ),
        }),
        onSubmit: ({ name, hostname }, { setSubmitting }) => {
            clearFlashes('server:settings:hardware')
        },
    })

    return (
        <FormSection title='Hardware'>
            <FormCard className='w-full'>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Hardware</FormCard.Title>
                        <div className='flex flex-col space-y-3 mt-3'>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>CPU</Dt>
                                    <Dd>{server.limits.cpu}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Memory</Dt>
                                    <Dd>{bytesToString(server.limits.memory)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Disk</Dt>
                                    <Dd>{bytesToString(server.limits.disk)}</Dd>
                                </dl>
                            </div>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>Used Bandwidth</Dt>
                                    <Dd>{bytesToString(server.usages.bandwidth)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Allotted Bandwidth</Dt>
                                    <Dd>
                                        {server.limits.bandwidth ? bytesToString(server.limits.bandwidth) : 'unlimited'}
                                    </Dd>
                                </dl>
                            </div>

                            <dl>
                                <Dt>IP Addresses</Dt>
                                {server.limits.addresses.ipv4.length === 0 &&
                                server.limits.addresses.ipv6.length === 0 ? (
                                    <Dd>There are no addresses associated with this server.</Dd>
                                ) : (
                                    <Display.Group className='mt-3'>
                                        {server.limits.addresses.ipv4.map(ip => (
                                            <Display.Row key={ip.id} className='grid-cols-1 md:grid-cols-3 text-sm'>
                                                <div>
                                                    <p className='description-small !text-xs'>Address</p>
                                                    <p className='font-semibold text-foreground'>
                                                        {ip.address}/{ip.cidr}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Gateway</p>
                                                    <p className='text-foreground font-semibold'>{ip.gateway}</p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Mac Address</p>
                                                    <p className='text-foreground font-semibold'>
                                                        {ip.macAddress || 'None'}
                                                    </p>
                                                </div>
                                            </Display.Row>
                                        ))}
                                    </Display.Group>
                                )}
                            </dl>
                        </div>
                    </FormCard.Body>
                </form>
            </FormCard>
            <BootOrderContainer />
            <MountsContainer />
        </FormSection>
    )
}

const MountsContainer = () => {
    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Mounts</FormCard.Title>
            </FormCard.Body>
            <FormCard.Footer>
                <Button type='submit' variant='filled' color='success' size='sm'>
                    Save
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

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
                const oldIndex = items.indexOf(active.id as string)
                const newIndex = items.indexOf(over.id as string)

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
                                                <button className='bg-transparent p-1' {...attributes} {...listeners}>
                                                    <img
                                                        src={DragVerticalIcon}
                                                        className='h-6 w-6 dark:invert'
                                                        alt='Drag button icon'
                                                    />
                                                </button>
                                                <div className='flex justify-between w-full items-center'>
                                                    {disk.displayName ? (
                                                        <div>
                                                            <span className='text-foreground'>{disk.displayName}</span>
                                                        </div>
                                                    ) : (
                                                        <span className='text-foreground'>{disk.name}</span>
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
                                    <span className='text-foreground'>{device.name}</span>
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

export default HardwareContainer
