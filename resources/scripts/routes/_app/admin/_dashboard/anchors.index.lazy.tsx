import {
    enrollmentQueries,
    rejectEnrollment,
    useEnrollments,
} from '@/features/anchors/api'
import AnchorStatusCell from '@/features/anchors/components/AnchorStatusCell.tsx'
import type { AnchorEnrollmentQueueItem } from '@/features/anchors/types.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconPlus, IconTransform } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import byteSize from 'byte-size'
import { formatDistanceToNowStrict } from 'date-fns'

import { buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'
import { queryClient } from '@/lib/query-client.ts'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/anchors/')({
    component: EnrollmentQueueIndex,
})

/**
 * What the machine reported, as one scannable line.
 *
 * Hardware and address only: this is the evidence for "is this the box I just
 * racked", and padding it with everything in the bag would bury the parts that
 * answer it.
 */
const reported = (item: AnchorEnrollmentQueueItem) => {
    const facts = (item.reportedFacts ?? {}) as Record<string, unknown>
    const cpu = (facts.cpu ?? {}) as Record<string, number>
    const parts: string[] = []

    if (cpu.threads) parts.push(`${cpu.threads} threads`)

    if (typeof facts.memory_bytes === 'number') {
        const memory = byteSize(facts.memory_bytes, { units: 'iec' })
        parts.push(`${memory.value} ${memory.unit}`)
    }

    if (typeof facts.observed_source_ip === 'string') {
        parts.push(facts.observed_source_ip)
    }

    return parts.join(' · ') || '—'
}

function EnrollmentQueueIndex() {
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useEnrollments(queryParams)

    const reject = useMutation({
        mutationFn: rejectEnrollment,
        onSuccess: async () => {
            toast.add({ title: 'Machine turned away', type: 'success' })
            await queryClient.invalidateQueries({
                queryKey: enrollmentQueries.all(),
            })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Could not turn this machine away'),
                type: 'error',
            }),
    })

    const renderActions = (item: AnchorEnrollmentQueueItem) => (
        <>
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => reject.mutate(item.id)}
            >
                Turn away
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<AnchorEnrollmentQueueItem>[] = [
        {
            header: 'Machine',
            accessorKey: 'name',
            enableHiding: false,
            cell: ({ row }) => (
                <Link
                    to={'/admin/anchors/$anchorId'}
                    params={{ anchorId: String(row.original.id) }}
                    className={buttonVariants({ variant: 'link' })}
                >
                    {row.original.name}
                </Link>
            ),
        },
        {
            header: 'Role',
            accessorKey: 'mode',
            cell: ({ cell }) => (
                <span className={'capitalize'}>{cell.getValue<string>()}</span>
            ),
        },
        {
            header: 'Reported',
            id: 'reported',
            cell: ({ row }) => reported(row.original),
        },
        {
            header: 'Waiting since',
            accessorKey: 'enrolledAt',
            cell: ({ cell }) => {
                const value = cell.getValue<string | null>()

                return value
                    ? formatDistanceToNowStrict(new Date(value), {
                          addSuffix: true,
                      })
                    : '—'
            },
        },
        {
            header: 'Agent',
            id: 'agent',
            cell: ({ row }) => <AnchorStatusCell anchor={row.original} />,
        },
        actionsColumn<AnchorEnrollmentQueueItem>(({ row }) => (
            <>{renderActions(row.original)}</>
        )),
    ]

    return (
        <>
            <Heading>Anchors</Heading>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                emptyState={
                    <SimpleEmptyState
                        icon={IconTransform}
                        title={'No machines waiting'}
                        description={
                            'Run the install command on a Proxmox host to enroll it.'
                        }
                        action={
                            <Link
                                className={buttonVariants()}
                                to={'/admin/nodes/create'}
                            >
                                <IconPlus className={'size-4'} />
                                Enroll a node
                            </Link>
                        }
                    />
                }
                mobileRow={row => {
                    const item = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle>
                                    <Link
                                        to={'/admin/anchors/$anchorId'}
                                        params={{
                                            anchorId: String(item.id),
                                        }}
                                        className={buttonVariants({
                                            variant: 'link',
                                        })}
                                    >
                                        {item.name}
                                    </Link>
                                </ItemTitle>
                                <ItemDescription>
                                    {reported(item)}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(item)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={
                    <Link
                        className={buttonVariants()}
                        to={'/admin/nodes/create'}
                    >
                        <IconPlus className={'size-4'} />
                        Enroll a node
                    </Link>
                }
                {...tableProps}
            />
        </>
    )
}
