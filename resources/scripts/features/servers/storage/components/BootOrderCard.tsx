import {
    type BootDevice,
    storageQueries,
    updateBootOrder,
    useBootOrder,
} from '@/features/servers/storage/api.ts'
import {
    IconArrowDown,
    IconArrowUp,
    IconGripVertical,
    IconPlus,
    IconX,
} from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import byteSize from 'byte-size'
import { useEffect, useMemo, useState } from 'react'

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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemGroup,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

const label = (device: BootDevice) => {
    const size = byteSize(device.size, { units: 'iec', precision: 0 })
    return `${device.interface} · ${size.value} ${size.unit}`
}

const interfaces = (devices: BootDevice[]) =>
    devices.map(d => d.interface).join(',')

interface Props {
    uuid: string
}

const BootOrderCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data, isLoading, isError, refetch } = useBootOrder(uuid)

    const [order, setOrder] = useState<BootDevice[]>([])
    const [unused, setUnused] = useState<BootDevice[]>([])

    useEffect(() => {
        if (data) {
            setOrder(data.bootOrder)
            setUnused(data.unusedDevices)
        }
    }, [data])

    const dirty = useMemo(
        () => (data ? interfaces(order) !== interfaces(data.bootOrder) : false),
        [order, data]
    )

    const move = (index: number, delta: number) =>
        setOrder(prev => {
            const next = [...prev]
            const target = index + delta
            if (target < 0 || target >= next.length) return prev
            ;[next[index], next[target]] = [next[target], next[index]]
            return next
        })

    const remove = (device: BootDevice) => {
        setOrder(prev => prev.filter(d => d.interface !== device.interface))
        setUnused(prev => [...prev, device])
    }

    const add = (device: BootDevice) => {
        setUnused(prev => prev.filter(d => d.interface !== device.interface))
        setOrder(prev => [...prev, device])
    }

    const { mutate: save, isPending } = useMutation({
        mutationFn: () =>
            updateBootOrder(
                uuid,
                order.map(d => d.interface)
            ),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: storageQueries.bootOrder(uuid).queryKey,
            })
            toast.add({ title: 'Boot order updated', type: 'success' })
        },
        onError: () =>
            toast.add({ title: 'Failed to update boot order', type: 'error' }),
    })

    const addDeviceMenu = unused.length > 0 && (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant={'outline'}>
                    <IconPlus className={'size-4'} />
                    Add device
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align={'start'}>
                {unused.map(device => (
                    <DropdownMenuItem
                        key={device.interface}
                        onClick={() => add(device)}
                    >
                        <span className={'font-mono'}>{label(device)}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    )

    return (
        <Card>
            <CardHeader>
                <CardTitle>Boot Order</CardTitle>
                <CardDescription>
                    The order devices are tried at boot. Reorder with the
                    arrows.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-3'}>
                {isError && !data ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : isLoading ? (
                    <Skeleton className={'h-32 w-full'} />
                ) : order.length > 0 ? (
                    <ItemGroup className={'gap-3'}>
                        {order.map((device, index) => (
                            <Item
                                key={device.interface}
                                variant={'muted'}
                                size={'sm'}
                            >
                                <ItemMedia>
                                    <IconGripVertical
                                        className={'text-muted-foreground'}
                                        size={16}
                                    />
                                </ItemMedia>
                                <ItemContent className={'min-w-0'}>
                                    <ItemTitle className={'truncate font-mono'}>
                                        {label(device)}
                                    </ItemTitle>
                                </ItemContent>
                                <ItemActions className={'ml-auto'}>
                                    <Button
                                        variant={'ghost'}
                                        size={'icon'}
                                        aria-label={`Move ${label(device)} up`}
                                        disabled={index === 0}
                                        onClick={() => move(index, -1)}
                                    >
                                        <IconArrowUp className={'h-4 w-4'} />
                                    </Button>
                                    <Button
                                        variant={'ghost'}
                                        size={'icon'}
                                        aria-label={`Move ${label(device)} down`}
                                        disabled={index === order.length - 1}
                                        onClick={() => move(index, 1)}
                                    >
                                        <IconArrowDown className={'h-4 w-4'} />
                                    </Button>
                                    <Button
                                        variant={'ghost'}
                                        size={'icon'}
                                        aria-label={`Remove ${label(device)} from boot order`}
                                        onClick={() => remove(device)}
                                    >
                                        <IconX className={'h-4 w-4'} />
                                    </Button>
                                </ItemActions>
                            </Item>
                        ))}
                    </ItemGroup>
                ) : (
                    <SimpleEmptyState
                        icon={IconGripVertical}
                        title={'No boot devices'}
                        description={'No devices are set to boot.'}
                        action={addDeviceMenu}
                    />
                )}

                {data && order.length > 0 && addDeviceMenu}
            </CardContent>
            {dirty && (
                <CardFooter className={'flex justify-end gap-3'}>
                    <Button
                        variant={'outline'}
                        onClick={() => {
                            if (data) {
                                setOrder(data.bootOrder)
                                setUnused(data.unusedDevices)
                            }
                        }}
                        disabled={isPending}
                    >
                        Reset
                    </Button>
                    <Button onClick={() => save()} loading={isPending}>
                        Save changes
                    </Button>
                </CardFooter>
            )}
        </Card>
    )
}

export default BootOrderCard
