import { useServers } from '@/features/servers/admin/api.ts'
import PowerStateBadge from '@/features/servers/components/PowerStateBadge.tsx'
import { cn } from '@/utils'
import { IconServer } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import byteSize from 'byte-size'

import { Badge } from '@/components/ui/Badge.tsx'
import { buttonVariants } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
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
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

interface Props {
    userId: number
}

const PREVIEW_COUNT = 5

const format = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 0 })

    return `${size.value} ${size.unit}`
}

/**
 * The first few servers the account owns, with the same power badge the admin list uses. The full
 * table — searchable, sortable, with the row actions — is one click away on the Servers tab.
 */
const UserServersCard = ({ userId }: Props) => {
    const { data, isLoading, isError, refetch } = useServers({
        page: 1,
        perPage: PREVIEW_COUNT,
        filters: { user_id: userId },
    })

    const total = data?.pagination.total ?? 0

    return (
        <Card>
            <CardHeader>
                <CardTitle>Servers</CardTitle>
                {total > 0 && (
                    <CardAction>
                        <Link
                            className={buttonVariants({
                                variant: 'outline',
                                size: 'sm',
                            })}
                            to={`/admin/users/${userId}/servers` as string}
                        >
                            View all {total}
                        </Link>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent
                className={
                    isLoading || isError || total === 0
                        ? 'grid min-h-[12rem] flex-1 place-items-center'
                        : 'flex flex-1 flex-col'
                }
            >
                {isError && !data ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : isLoading || !data ? (
                    <Skeleton className={'h-40 w-full'} />
                ) : data.items.length === 0 ? (
                    <SimpleEmptyState
                        icon={IconServer}
                        title={'No servers'}
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Power</TableHead>
                                <TableHead>vCPU</TableHead>
                                <TableHead>Memory</TableHead>
                                <TableHead>Disk</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.items.map(server => (
                                <TableRow key={server.id}>
                                    <TableCell>
                                        <span
                                            className={
                                                'flex items-center gap-1.5'
                                            }
                                        >
                                            <Link
                                                className={cn(
                                                    buttonVariants({
                                                        variant: 'link',
                                                    }),
                                                    'h-auto px-0'
                                                )}
                                                to={
                                                    `/admin/servers/${server.id}` as string
                                                }
                                            >
                                                {server.name}
                                            </Link>
                                            {server.suspendedAt && (
                                                <Badge variant={'destructive'}>
                                                    Suspended
                                                </Badge>
                                            )}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <PowerStateBadge
                                            state={server.powerState}
                                        />
                                    </TableCell>
                                    <TableCell
                                        className={
                                            'whitespace-nowrap tabular-nums'
                                        }
                                    >
                                        {server.cpu}
                                    </TableCell>
                                    <TableCell
                                        className={
                                            'whitespace-nowrap tabular-nums'
                                        }
                                    >
                                        {format(server.memory)}
                                    </TableCell>
                                    <TableCell
                                        className={
                                            'whitespace-nowrap tabular-nums'
                                        }
                                    >
                                        {format(server.disk)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    )
}

export default UserServersCard
