import { deleteToken, tokenQueries, useTokens } from '@/features/tokens/api.ts'
import CreateTokenModal from '@/features/tokens/components/CreateTokenModal.tsx'
import EditTokenNetworksModal from '@/features/tokens/components/EditTokenNetworksModal.tsx'
import {
    type ApiKey,
    type PaginatedApiKeys,
    summarizeAbilities,
    summarizeAllowedNetworks,
} from '@/features/tokens/types.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { formatDistanceToNow } from 'date-fns'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
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

export const Route = createLazyFileRoute('/_app/admin/_dashboard/tokens')({
    component: TokensIndex,
})

const formatLastUsed = (value: string | null) =>
    value ? formatDistanceToNow(new Date(value), { addSuffix: true }) : 'Never'

function TokensIndex() {
    const [editingToken, setEditingToken] = useState<ApiKey | null>(null)
    const confirm = useConfirmationStore(state => state.confirm)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } = useTokens(queryParams)
    const mutate = useQueryMutator<PaginatedApiKeys>(
        tokenQueries.list(queryParams).queryKey
    )

    const { mutate: revoke } = useMutation({
        mutationFn: (token: ApiKey) => deleteToken(token.id),
        onSuccess: (_, token) => {
            mutate(data =>
                data
                    ? {
                          ...data,
                          items: data.items.filter(t => t.id !== token.id),
                      }
                    : data
            )
            toast.add({ title: 'API token revoked', type: 'success' })
        },
        onError: () =>
            toast.add({ title: 'Failed to revoke token', type: 'error' }),
    })

    const handleDelete = async (token: ApiKey) => {
        const confirmed = await confirm({
            title: 'Revoke API token',
            description: `Any integration using “${token.name}” will immediately lose access. This cannot be undone.`,
        })
        if (!confirmed) return

        revoke(token)
    }

    const renderActions = (token: ApiKey) => (
        <>
            <DropdownMenuItem onClick={() => setEditingToken(token)}>
                Edit restrictions
            </DropdownMenuItem>
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => handleDelete(token)}
            >
                Revoke
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<ApiKey>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            enableHiding: false,
            meta: { skeletonWidth: '8rem' },
        },
        {
            header: 'Abilities',
            id: 'abilities',
            meta: { skeletonWidth: '6rem' },
            cell: ({ row }) => (
                <Badge variant={'secondary'}>
                    {summarizeAbilities(row.original.abilities)}
                </Badge>
            ),
        },
        {
            header: 'Network access',
            id: 'networkAccess',
            meta: { skeletonWidth: '8rem' },
            cell: ({ row }) => (
                <span className={'whitespace-nowrap'}>
                    {summarizeAllowedNetworks(row.original.allowedNetworks)}
                </span>
            ),
        },
        {
            header: 'Created by',
            id: 'createdBy',
            meta: { skeletonWidth: '8rem' },
            cell: ({ row }) =>
                row.original.createdBy?.email ?? (
                    <span className={'text-muted-foreground'}>—</span>
                ),
        },
        {
            header: 'Last used',
            accessorKey: 'lastUsedAt',
            meta: { skeletonWidth: '6rem' },
            cell: ({ cell }) => {
                const value = cell.getValue<string | null>()

                return value ? (
                    formatLastUsed(value)
                ) : (
                    <span className={'text-muted-foreground'}>Never</span>
                )
            },
        },
        actionsColumn<ApiKey>(({ row }) => renderActions(row.original)),
    ]

    return (
        <>
            <Heading>API Tokens</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                toolbar
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                mobileRow={row => {
                    const token = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle className={'truncate'}>
                                    {token.name}
                                </ItemTitle>
                                <div className={'flex flex-wrap gap-2'}>
                                    <Badge variant={'secondary'}>
                                        {summarizeAbilities(token.abilities)}
                                    </Badge>
                                    <Badge variant={'outline'}>
                                        {summarizeAllowedNetworks(
                                            token.allowedNetworks
                                        )}
                                    </Badge>
                                    <span
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        {token.lastUsedAt
                                            ? `Last used ${formatLastUsed(token.lastUsedAt)}`
                                            : 'Never used'}
                                    </span>
                                </div>
                                <ItemDescription className={'truncate'}>
                                    {token.createdBy?.email
                                        ? `Created by ${token.createdBy.email}`
                                        : 'No creator recorded'}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(token)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                rightActions={<CreateTokenModal mutate={mutate} />}
                {...tableProps}
            />
            <EditTokenNetworksModal
                token={editingToken}
                onClose={() => setEditingToken(null)}
                mutate={mutate}
            />
        </>
    )
}
