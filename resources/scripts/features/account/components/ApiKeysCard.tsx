import {
    type ApiKey as ApiKeyType,
    apiKeyQueries,
    deleteApiKey,
    useApiKeys,
} from '@/features/account/api-keys/api.ts'
import ApiKey from '@/features/account/components/ApiKey.tsx'
import ApiKeyCreateDialog from '@/features/account/components/ApiKeyCreateDialog.tsx'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'
import { IconApi } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { toast } from 'sonner'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { OverflowItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const ApiKeysCard = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const mutate = useQueryMutator<ApiKeyType[]>(apiKeyQueries.all())
    const [createOpen, setCreateOpen] = useState(false)

    const { data: keys, isLoading, isError, refetch } = useApiKeys()

    const { mutate: revoke } = useMutation({
        mutationFn: (apiKey: ApiKeyType) => deleteApiKey(apiKey.id),
        onSuccess: (_, apiKey) => {
            mutate(keys => keys?.filter(k => k.id !== apiKey.id))
            toast.success('API token revoked')
        },
        onError: () => toast.error('Failed to revoke token'),
    })

    const handleDelete = async (apiKey: ApiKeyType) => {
        const confirmed = await confirm({
            title: 'Revoke API token',
            description: `Any application using “${apiKey.name}” will immediately lose access. This cannot be undone.`,
        })
        if (!confirmed) return

        revoke(apiKey)
    }

    return (
        <>
            <Card className={'flex flex-col'}>
                <CardHeader>
                    <CardTitle>API Tokens</CardTitle>
                    <CardDescription>
                        Personal access tokens that authenticate to the API on
                        your behalf.
                    </CardDescription>
                    {keys && keys.length > 0 && (
                        <CardAction>
                            <Button
                                variant={'outline'}
                                onClick={() => setCreateOpen(true)}
                            >
                                Create token
                            </Button>
                        </CardAction>
                    )}
                </CardHeader>
                <CardContent
                    className={cn(
                        (isLoading || isError || keys?.length === 0) &&
                            'grid min-h-[12rem] flex-1 place-items-center'
                    )}
                >
                    {isError && !keys ? (
                        <CollectionErrorState onRetry={refetch} />
                    ) : isLoading || !keys ? (
                        <Skeleton className={'h-40 w-full'} />
                    ) : keys.length > 0 ? (
                        <OverflowItemGroup
                            title={'API Tokens'}
                            rows={keys.map(key => (
                                <ApiKey
                                    key={key.id}
                                    apiKey={key}
                                    onDelete={handleDelete}
                                />
                            ))}
                        />
                    ) : (
                        <SimpleEmptyState
                            icon={IconApi}
                            title={'No API tokens'}
                            description={
                                'You haven’t created any API tokens yet.'
                            }
                            action={
                                <Button onClick={() => setCreateOpen(true)}>
                                    Create token
                                </Button>
                            }
                        />
                    )}
                </CardContent>
            </Card>

            <ApiKeyCreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
        </>
    )
}

export default ApiKeysCard
