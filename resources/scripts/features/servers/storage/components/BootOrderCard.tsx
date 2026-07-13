import { useMutation, useQueryClient } from '@tanstack/react-query'
import {
    IconArrowDown,
    IconArrowUp,
    IconGripVertical,
    IconPlus,
    IconX,
} from '@tabler/icons-react'
import byteSize from 'byte-size'
import { useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'

import {
    storageQueries,
    updateBootOrder,
    useBootOrder,
    type BootDevice,
} from '@/features/servers/storage/api.ts'

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
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'

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
    const { data, isLoading } = useBootOrder(uuid)

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
            toast.success('Boot order updated')
        },
        onError: () => toast.error('Failed to update boot order'),
    })

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
                {isLoading ? (
                    <Skeleton className={'h-32 w-full'} />
                ) : order.length > 0 ? (
                    <ul className={'space-y-1'}>
                        {order.map((device, index) => (
                            <li
                                key={device.interface}
                                className={
                                    'flex items-center gap-2 rounded-md border p-2'
                                }
                            >
                                <IconGripVertical
                                    className={'text-muted-foreground'}
                                    size={16}
                                />
                                <span className={'font-mono text-sm'}>
                                    {label(device)}
                                </span>
                                <div className={'min-w-[1rem] grow'} />
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                >
                                    <IconArrowUp className={'h-4 w-4'} />
                                </Button>
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    disabled={index === order.length - 1}
                                    onClick={() => move(index, 1)}
                                >
                                    <IconArrowDown className={'h-4 w-4'} />
                                </Button>
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    onClick={() => remove(device)}
                                >
                                    <IconX className={'h-4 w-4'} />
                                </Button>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <SimpleEmptyState
                        icon={IconGripVertical}
                        title={'No boot devices'}
                        description={'No devices are set to boot.'}
                    />
                )}

                {unused.length > 0 && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant={'outline'} size={'sm'}>
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
                                    <span className={'font-mono'}>
                                        {label(device)}
                                    </span>
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
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
