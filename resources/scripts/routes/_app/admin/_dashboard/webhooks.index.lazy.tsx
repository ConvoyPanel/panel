import { useWebhooks } from '@/features/webhooks/api.ts'
import DeliveryStatusBadge from '@/features/webhooks/components/DeliveryStatusBadge.tsx'
import WebhookFormDialog from '@/features/webhooks/components/WebhookFormDialog.tsx'
import WebhookSecretDialog from '@/features/webhooks/components/WebhookSecret.tsx'
import useWebhookActions from '@/features/webhooks/hooks/use-webhook-actions.ts'
import { type Webhook, summarizeEvents } from '@/features/webhooks/types.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import { cn } from '@/utils'
import { IconPlus, IconWebhook } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { formatDistanceToNow } from 'date-fns'
import { useState } from 'react'

import { Badge } from '@/components/ui/Badge.tsx'
import { Button, buttonVariants } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

const LatestDelivery = ({ webhook }: { webhook: Webhook }) => {
    const delivery = webhook.latestDelivery

    if (!delivery) {
        return <span className={'text-muted-foreground'}>None</span>
    }

    return (
        <div className={'flex items-center gap-2 whitespace-nowrap'}>
            <DeliveryStatusBadge status={delivery.status} />
            <span className={'text-muted-foreground text-xs'}>
                {formatDistanceToNow(new Date(delivery.createdAt), {
                    addSuffix: true,
                })}
            </span>
        </div>
    )
}

const WebhookLink = ({
    webhook,
    className,
}: {
    webhook: Webhook
    className?: string
}) => (
    <Link
        className={className}
        to={'/admin/webhooks/$webhookUuid'}
        params={{ webhookUuid: webhook.uuid }}
        title={webhook.url}
    >
        <span className={'truncate'}>{webhook.url}</span>
    </Link>
)

const WebhooksIndex = () => {
    const [editing, setEditing] = useState<Webhook | 'new' | null>(null)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } =
        useWebhooks(queryParams)
    const { sendTest, confirmRotate, confirmDelete, secret, clearSecret } =
        useWebhookActions()

    const renderActions = (webhook: Webhook) => (
        <>
            <DropdownMenuItem asChild>
                <Link
                    to={'/admin/webhooks/$webhookUuid'}
                    params={{ webhookUuid: webhook.uuid }}
                >
                    Deliveries
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => setEditing(webhook)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => sendTest(webhook)}>
                Send test
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => confirmRotate(webhook)}>
                Rotate secret
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => confirmDelete(webhook)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<Webhook>[] = [
        {
            header: 'URL',
            accessorKey: 'url',
            enableHiding: false,
            meta: { skeletonWidth: '14rem' },
            cell: ({ row }) => (
                <WebhookLink
                    webhook={row.original}
                    className={cn(
                        buttonVariants({ variant: 'link' }),
                        'max-w-[24rem] justify-start px-0 font-mono text-xs'
                    )}
                />
            ),
        },
        {
            header: 'Description',
            accessorKey: 'description',
            meta: { skeletonWidth: '10rem' },
            cell: ({ cell }) =>
                cell.getValue<string | null>() ?? (
                    <span className={'text-muted-foreground'}>—</span>
                ),
        },
        {
            header: 'Events',
            id: 'events',
            meta: { skeletonWidth: '6rem' },
            cell: ({ row }) => (
                <Badge variant={'secondary'} className={'whitespace-nowrap'}>
                    {summarizeEvents(row.original.events)}
                </Badge>
            ),
        },
        {
            header: 'Status',
            accessorKey: 'enabled',
            meta: { skeletonWidth: '4rem' },
            cell: ({ cell }) =>
                cell.getValue<boolean>() ? (
                    'Enabled'
                ) : (
                    <Badge variant={'outline'}>Disabled</Badge>
                ),
        },
        {
            header: 'Latest delivery',
            id: 'latestDelivery',
            meta: { skeletonWidth: '8rem' },
            cell: ({ row }) => <LatestDelivery webhook={row.original} />,
        },
        actionsColumn<Webhook>(({ row }) => renderActions(row.original)),
    ]

    const addButton = (
        <Button onClick={() => setEditing('new')}>
            <IconPlus className={'size-4'} />
            Add webhook
        </Button>
    )

    return (
        <>
            <Heading>Webhooks</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                toolbar
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                rightActions={addButton}
                emptyState={
                    <SimpleEmptyState
                        icon={IconWebhook}
                        title={'No webhooks'}
                        action={addButton}
                    />
                }
                mobileRow={row => {
                    const webhook = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle className={'w-full min-w-0'}>
                                    <WebhookLink
                                        webhook={webhook}
                                        className={
                                            'flex min-w-0 font-mono text-xs'
                                        }
                                    />
                                </ItemTitle>
                                {webhook.description && (
                                    <ItemDescription className={'truncate'}>
                                        {webhook.description}
                                    </ItemDescription>
                                )}
                                <div
                                    className={
                                        'flex flex-wrap items-center gap-2'
                                    }
                                >
                                    <Badge variant={'secondary'}>
                                        {summarizeEvents(webhook.events)}
                                    </Badge>
                                    {!webhook.enabled && (
                                        <Badge variant={'outline'}>
                                            Disabled
                                        </Badge>
                                    )}
                                    <LatestDelivery webhook={webhook} />
                                </div>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(webhook)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                {...tableProps}
            />

            <WebhookFormDialog
                target={editing}
                close={() => setEditing(null)}
            />
            <WebhookSecretDialog secret={secret} onClose={clearSecret} />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/webhooks/')({
    component: WebhooksIndex,
})
