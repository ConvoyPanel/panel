import { AxiosError } from 'axios'
import byteSize from 'byte-size'
import { IconDatabaseOff, IconPlus } from '@tabler/icons-react'
import { ColumnDef } from '@tanstack/react-table'
import { useState } from 'react'
import { toast } from 'sonner'

import { useServer } from '@/features/servers/admin/api.ts'
import AddServerDiskModal from '@/features/servers/components/admin/detail/AddServerDiskModal.tsx'
import ResizeServerDiskModal from '@/features/servers/components/admin/detail/ResizeServerDiskModal.tsx'
import {
    type ServerDisk,
    useRemoveServerDisk,
    useServerDisks,
} from '@/features/servers/disks/api.ts'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { DataTable } from '@/components/ui/DataTable'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import Actions from '@/components/ui/Table/Actions.tsx'
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

    const renderActions = (disk: ServerDisk) => {
        if (disk.isPrimary) return null

        return (
            <>
                <DropdownMenuItem onSelect={() => setResizeDisk(disk)}>
                    Resize
                </DropdownMenuItem>
                <DropdownMenuItem
                    className='text-destructive focus:bg-destructive/10 focus:text-destructive'
                    onSelect={() => void handleRemove(disk)}
                >
                    Remove
                </DropdownMenuItem>
            </>
        )
    }

    const columns: ColumnDef<ServerDisk>[] = [
        {
            header: 'Interface',
            accessorKey: 'interface',
            cell: ({ row }) => (
                <span className='font-mono text-xs'>
                    {row.original.interface ?? (
                        <span className='text-muted-foreground'>pending</span>
                    )}
                </span>
            ),
        },
        {
            header: 'Type',
            accessorKey: 'isPrimary',
            cell: ({ row }) => (
                <Badge variant={row.original.isPrimary ? 'default' : 'outline'}>
                    {row.original.isPrimary ? 'Primary' : 'Data'}
                </Badge>
            ),
        },
        {
            header: 'Storage',
            accessorKey: 'storageName',
            cell: ({ row }) => row.original.storageName ?? '—',
        },
        {
            header: 'Size',
            accessorKey: 'size',
            cell: ({ row }) => formatBytes(row.original.size),
        },
        {
            id: 'actions',
            size: 40,
            cell: ({ row }) =>
                row.original.isPrimary ? (
                    <span className='text-muted-foreground text-xs'>Managed</span>
                ) : (
                    <Actions>{renderActions(row.original)}</Actions>
                ),
        },
    ]

    const addButton = (
        <Button onClick={() => setAddOpen(true)} disabled={!server}>
            <IconPlus className='size-4' />
            Add disk
        </Button>
    )

    return (
        <>
            <Heading>Disks</Heading>

            {!isLoading && disks?.length === 0 ? (
                <Card>
                    <CardContent>
                        <SimpleEmptyState
                            icon={IconDatabaseOff}
                            title='No attached disks'
                            description='Add a disk to expand this server’s storage.'
                            action={addButton}
                        />
                    </CardContent>
                </Card>
            ) : (
                <DataTable
                    toolbar
                    data={disks}
                    columns={columns}
                    isPlaceholderData={isLoading}
                    skeletonRows={3}
                    rightActions={addButton}
                    mobileRow={row => {
                        const disk = row.original

                        return (
                            <Item variant='muted' size='sm'>
                                <ItemContent className='min-w-0'>
                                    <ItemTitle className='font-mono text-xs'>
                                        {disk.interface ?? 'Pending'}
                                    </ItemTitle>
                                    <ItemDescription>
                                        {disk.storageName ?? 'Unknown storage'} ·{' '}
                                        {formatBytes(disk.size)}
                                    </ItemDescription>
                                    <Badge
                                        variant={
                                            disk.isPrimary ? 'default' : 'outline'
                                        }
                                        className='w-fit'
                                    >
                                        {disk.isPrimary ? 'Primary' : 'Data'}
                                    </Badge>
                                </ItemContent>
                                {!disk.isPrimary && (
                                    <ItemActions>
                                        <Actions>{renderActions(disk)}</Actions>
                                    </ItemActions>
                                )}
                            </Item>
                        )
                    }}
                />
            )}

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
