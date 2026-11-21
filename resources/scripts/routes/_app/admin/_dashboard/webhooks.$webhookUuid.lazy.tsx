import {
    redeliverWebhookDelivery,
    useWebhook,
    useWebhookDeliveries,
    useWebhookEvents,
    webhookQueries,
} from '@/features/webhooks/api.ts'
import DeliveryDialog from '@/features/webhooks/components/DeliveryDialog.tsx'
import DeliveryStatusBadge from '@/features/webhooks/components/DeliveryStatusBadge.tsx'
import WebhookFormDialog from '@/features/webhooks/components/WebhookFormDialog.tsx'
import WebhookSecretDialog from '@/features/webhooks/components/WebhookSecret.tsx'
import useWebhookActions from '@/features/webhooks/hooks/use-webhook-actions.ts'
import {
    type WebhookDelivery,
    type WebhookDeliveryStatus,
    deliveryStatusLabels,
    entryMatches,
    summarizeEvents,
    webhookName,
} from '@/features/webhooks/types.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'
import { IconDots, IconSend } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { format, formatDistanceToNow } from 'date-fns'
import { parseAsString, useQueryState } from 'nuqs'
import { useMemo, useState } from 'react'

import { Badge } from '@/components/ui/Badge.tsx'
import { Button, buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import DataTableFilteredEmpty from '@/components/ui/DataTable/DataTableFilteredEmpty.tsx'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import { PageToolbar } from '@/components/ui/PageToolbar'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import Skeleton from '@/components/ui/Skeleton.tsx'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const ALL = 'all'

const STATUS_OPTIONS = [
    { value: ALL, label: 'All statuses' },
    ...(Object.keys(deliveryStatusLabels) as WebhookDeliveryStatus[]).map(
        status => ({ value: status, label: deliveryStatusLabels[status] })
    ),
]

/** The test button's event, which a webhook gets whatever it subscribes to. */
const PING_EVENT = 'webhook.ping'

const DeliveryTime = ({ delivery }: { delivery: WebhookDelivery }) => (
    <span
        className={'whitespace-nowrap'}
        title={format(new Date(delivery.createdAt), 'PPpp')}
    >
        {formatDistanceToNow(new Date(delivery.createdAt), {
            addSuffix: true,
        })}
    </span>
)

const WebhookDetail = () => {
    const { webhookUuid } = Route.useParams()
    const navigate = useNavigate()
    const { data: webhook } = useWebhook(webhookUuid)
    const { data: catalogue } = useWebhookEvents()

    const [editing, setEditing] = useState(false)
    const [viewing, setViewing] = useState<WebhookDelivery | null>(null)

    const [status, setStatus] = useQueryState(
        'status',
        parseAsString.withDefault(ALL)
    )
    const [event, setEvent] = useQueryState(
        'event',
        parseAsString.withDefault(ALL)
    )
    const filtered = status !== ALL || event !== ALL

    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } = useWebhookDeliveries(
        webhookUuid,
        {
            page: queryParams.page,
            perPage: queryParams.perPage,
            filters: {
                status: status === ALL ? null : status,
                event: event === ALL ? null : event,
            },
        }
    )

    const mutateWebhook = useQueryMutator(
        webhookQueries.detail(webhookUuid).queryKey
    )
    const mutateLists = useQueryMutator(webhookQueries.lists())

    const { sendTest, confirmRotate, confirmDelete, secret, clearSecret } =
        useWebhookActions({
            onDeleted: () => navigate({ to: '/admin/webhooks', replace: true }),
        })

    const { mutate: redeliver } = useMutation({
        mutationFn: (delivery: WebhookDelivery) =>
            redeliverWebhookDelivery(webhookUuid, delivery.uuid),
        onSuccess: updated => {
            toast.add({ title: 'Redelivery queued', type: 'success' })
            setViewing(current =>
                current?.uuid === updated.uuid ? updated : current
            )
            void mutateWebhook()
            void mutateLists()
        },
        onError: () =>
            toast.add({ title: 'Failed to redeliver', type: 'error' }),
    })

    // The events this webhook can have received, so the filter offers only
    // those rather than the whole catalogue.
    const eventOptions = useMemo(() => {
        const subscribed = (catalogue ?? []).filter(name =>
            webhook?.events.some(entry => entryMatches(entry, name))
        )

        return [
            { value: ALL, label: 'All events' },
            ...[PING_EVENT, ...subscribed].map(name => ({
                value: name,
                label: name,
            })),
        ]
    }, [catalogue, webhook?.events])

    if (!webhook) {
        return (
            <>
                <Skeleton className={'h-8 w-64'} />
                <Skeleton className={'h-96 w-full'} />
            </>
        )
    }

    const changeFilter = (set: (value: string) => void) => (value: string) => {
        set(value)
        // A narrower filter can leave the current page past the last one.
        tableProps.setPage(1)
    }

    const clearFilters = () => {
        void setStatus(null)
        void setEvent(null)
        tableProps.setPage(1)
    }

    const renderActions = (delivery: WebhookDelivery) => (
        <>
            <DropdownMenuItem onClick={() => setViewing(delivery)}>
                View
            </DropdownMenuItem>
            <DropdownMenuItem
                disabled={delivery.status === 'pending'}
                onClick={() => redeliver(delivery)}
            >
                Redeliver
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<WebhookDelivery>[] = [
        {
            header: 'Event',
            accessorKey: 'event',
            enableHiding: false,
            meta: { skeletonWidth: '12rem' },
            cell: ({ row }) => (
                <button
                    className={cn(
                        buttonVariants({ variant: 'link' }),
                        'px-0 font-mono text-xs'
                    )}
                    onClick={() => setViewing(row.original)}
                >
                    {row.original.event}
                </button>
            ),
        },
        {
            header: 'Status',
            accessorKey: 'status',
            meta: { skeletonWidth: '5rem' },
            cell: ({ row }) => (
                <DeliveryStatusBadge status={row.original.status} />
            ),
        },
        {
            header: 'Response',
            accessorKey: 'responseStatus',
            meta: { skeletonWidth: '3rem' },
            cell: ({ cell }) =>
                cell.getValue<number | null>() ?? (
                    <span className={'text-muted-foreground'}>—</span>
                ),
        },
        {
            header: 'Attempts',
            accessorKey: 'attempts',
            meta: { skeletonWidth: '2rem' },
        },
        {
            header: 'Time',
            accessorKey: 'createdAt',
            meta: { skeletonWidth: '6rem' },
            cell: ({ row }) => <DeliveryTime delivery={row.original} />,
        },
        actionsColumn<WebhookDelivery>(({ row }) =>
            renderActions(row.original)
        ),
    ]

    const testButton = (
        <Button variant={'outline'} onClick={() => sendTest(webhook)}>
            <IconSend className={'size-4'} />
            Send test
        </Button>
    )

    return (
        <>
            <div className={'space-y-1'}>
                <div className={'flex flex-wrap items-center gap-3'}>
                    <Heading className={'min-w-0 break-all'}>
                        {webhookName(webhook)}
                    </Heading>
                    <Badge variant={'secondary'}>
                        {summarizeEvents(webhook.events)}
                    </Badge>
                    {!webhook.enabled && (
                        <Badge variant={'outline'}>Disabled</Badge>
                    )}
                </div>
                {webhook.description && (
                    <p
                        className={
                            'text-muted-foreground font-mono text-sm break-all'
                        }
                    >
                        {webhook.url}
                    </p>
                )}
            </div>

            <PageToolbar
                actions={
                    <>
                        {testButton}
                        <Button
                            variant={'outline'}
                            onClick={() => setEditing(true)}
                        >
                            Edit
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant={'outline'}
                                    size={'icon'}
                                    aria-label={'More actions'}
                                >
                                    <IconDots className={'size-4'} />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align={'end'}>
                                <DropdownMenuItem
                                    onClick={() => confirmRotate(webhook)}
                                >
                                    Rotate secret
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant={'destructive'}
                                    onClick={() => confirmDelete(webhook)}
                                >
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </>
                }
            >
                <Select
                    value={status}
                    onValueChange={value =>
                        value && changeFilter(setStatus)(value)
                    }
                >
                    <SelectTrigger
                        className={'w-40'}
                        aria-label={'Filter by status'}
                    >
                        <SelectValue>
                            {(value: string) =>
                                STATUS_OPTIONS.find(
                                    option => option.value === value
                                )?.label ?? 'All statuses'
                            }
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        {STATUS_OPTIONS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select
                    value={event}
                    onValueChange={value =>
                        value && changeFilter(setEvent)(value)
                    }
                >
                    <SelectTrigger
                        className={'w-64'}
                        aria-label={'Filter by event'}
                    >
                        <SelectValue>
                            {(value: string) =>
                                value === ALL ? 'All events' : value
                            }
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        {eventOptions.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </PageToolbar>

            <DataTable
                data={data}
                columns={columns}
                paginated
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                emptyState={
                    filtered ? (
                        <DataTableFilteredEmpty onClear={clearFilters} />
                    ) : (
                        <SimpleEmptyState
                            icon={IconSend}
                            title={'No deliveries'}
                            action={testButton}
                        />
                    )
                }
                mobileRow={row => {
                    const delivery = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0'}>
                                    <button
                                        className={
                                            'truncate text-left font-mono text-xs'
                                        }
                                        onClick={() => setViewing(delivery)}
                                    >
                                        {delivery.event}
                                    </button>
                                </ItemTitle>
                                <div
                                    className={
                                        'flex flex-wrap items-center gap-2'
                                    }
                                >
                                    <DeliveryStatusBadge
                                        status={delivery.status}
                                    />
                                    {delivery.responseStatus !== null && (
                                        <Badge
                                            variant={'outline'}
                                            className={'font-mono'}
                                        >
                                            {delivery.responseStatus}
                                        </Badge>
                                    )}
                                </div>
                                <ItemDescription>
                                    <DeliveryTime delivery={delivery} />
                                    {` · ${delivery.attempts} ${delivery.attempts === 1 ? 'attempt' : 'attempts'}`}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(delivery)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                {...tableProps}
            />

            <WebhookFormDialog
                target={editing ? webhook : null}
                close={() => setEditing(false)}
            />
            <WebhookSecretDialog secret={secret} onClose={clearSecret} />
            <DeliveryDialog
                delivery={viewing}
                onClose={() => setViewing(null)}
                onRedeliver={redeliver}
            />
        </>
    )
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/webhooks/$webhookUuid'
)({
    component: WebhookDetail,
})
