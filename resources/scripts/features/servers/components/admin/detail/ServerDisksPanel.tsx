import byteSize from 'byte-size'
import { AxiosError } from 'axios'
import { useState } from 'react'
import { toast } from 'sonner'
import { IconPlus } from '@tabler/icons-react'

import { useServer } from '@/features/servers/admin/api.ts'
import {
    type ServerDisk,
    useRemoveServerDisk,
    useServerDisks,
} from '@/features/servers/disks/api.ts'

import AddServerDiskModal from '@/features/servers/components/admin/detail/AddServerDiskModal.tsx'
import ResizeServerDiskModal from '@/features/servers/components/admin/detail/ResizeServerDiskModal.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'
import { Heading } from '@/components/ui/Typography'

interface Props {
    serverId: number
}

const errorMessage = (e: unknown, fallback: string): string =>
    e instanceof AxiosError && e.response?.data?.message
        ? e.response.data.message
        : fallback

const formatBytes = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${size.value} ${size.unit}`
}

const ServerDisksPanel = ({ serverId }: Props) => {
    const { data: server } = useServer(serverId)
    const { data: disks, isLoading } = useServerDisks(serverId)
    const remove = useRemoveServerDisk(serverId)
    const confirm = useConfirmationStore(state => state.confirm)

    const [addOpen, setAddOpen] = useState(false)
    const [resizeDisk, setResizeDisk] = useState<ServerDisk | null>(null)

    const handleRemove = async (disk: ServerDisk) => {
        const confirmed = await confirm({
            title: 'Remove disk',
            description: `This detaches ${disk.interface ?? 'the disk'} and permanently destroys its data on Proxmox. This cannot be undone.`,
        })
        if (!confirmed) return

        try {
            await remove.mutateAsync(disk.id)
            toast.success('Disk removed')
        } catch (e) {
            toast.error(errorMessage(e, 'Failed to remove disk'))
        }
    }

    return (
        <>
            <div className='flex flex-wrap items-center justify-between gap-3'>
                <Heading>Disks</Heading>
                <Button onClick={() => setAddOpen(true)} disabled={!server}>
                    <IconPlus className='mr-2 size-4' />
                    Add disk
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Attached disks</CardTitle>
                </CardHeader>
                <CardContent>
                    {isLoading || !disks ? (
                        <Skeleton className='h-32 w-full' />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Interface</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Storage</TableHead>
                                    <TableHead>Size</TableHead>
                                    <TableHead className='text-right'>
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {disks.map(disk => (
                                    <TableRow key={disk.id}>
                                        <TableCell className='font-mono text-xs'>
                                            {disk.interface ?? (
                                                <span className='text-muted-foreground'>
                                                    pending
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    disk.isPrimary
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {disk.isPrimary
                                                    ? 'Primary'
                                                    : 'Data'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {disk.storageName ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatBytes(disk.size)}
                                        </TableCell>
                                        <TableCell className='text-right'>
                                            {disk.isPrimary ? (
                                                <span className='text-xs text-muted-foreground'>
                                                    Managed with the server
                                                </span>
                                            ) : (
                                                <div className='flex justify-end gap-2'>
                                                    <Button
                                                        variant='outline'
                                                        size='sm'
                                                        onClick={() =>
                                                            setResizeDisk(disk)
                                                        }
                                                    >
                                                        Resize
                                                    </Button>
                                                    <Button
                                                        variant='destructiveOutline'
                                                        size='sm'
                                                        onClick={() =>
                                                            handleRemove(disk)
                                                        }
                                                    >
                                                        Remove
                                                    </Button>
                                                </div>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {server && (
                <AddServerDiskModal
                    serverId={serverId}
                    nodeId={server.nodeId}
                    open={addOpen}
                    onOpenChange={setAddOpen}
                />
            )}

            <ResizeServerDiskModal
                serverId={serverId}
                disk={resizeDisk}
                onOpenChange={open => !open && setResizeDisk(null)}
            />
        </>
    )
}

export default ServerDisksPanel
