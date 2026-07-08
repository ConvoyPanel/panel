import { useMutation } from '@tanstack/react-query'
import { IconApi } from '@tabler/icons-react'
import { useState } from 'react'
import { toast } from 'sonner'

import {
    apiKeyQueries,
    deleteApiKey,
    useApiKeys,
    type ApiKey as ApiKeyType,
} from '@/features/account/api-keys/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

import ApiKey from '@/features/account/components/ApiKey.tsx'
import ApiKeyCreateDialog from '@/features/account/components/ApiKeyCreateDialog.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { cn } from '@/utils'

const ApiKeysCard = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const mutate = useQueryMutator<ApiKeyType[]>(apiKeyQueries.all())
    const [createOpen, setCreateOpen] = useState(false)

    const { data: keys, isLoading } = useApiKeys()

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
            <Card className={'@md:col-span-2'}>
                <CardHeader>
                    <CardTitle>API Tokens</CardTitle>
                    <CardDescription>
                        Personal access tokens that authenticate to the API on
                        your behalf.
                    </CardDescription>
                </CardHeader>
                <CardContent
                    className={cn(
                        'min-h-[14rem]',
                        (isLoading || keys?.length === 0) &&
                            'grid place-items-center'
                    )}
                >
                    {isLoading || !keys ? (
                        <Skeleton className={'h-40 w-full'} />
                    ) : keys.length > 0 ? (
                        <ul className={'divide-y'}>
                            {keys.map(key => (
                                <li key={key.id}>
                                    <ApiKey
                                        apiKey={key}
                                        onDelete={handleDelete}
                                    />
                                </li>
                            ))}
                        </ul>
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
                {keys && keys.length > 0 && (
                    <CardFooter className={'flex justify-end'}>
                        <Button onClick={() => setCreateOpen(true)}>
                            Create token
                        </Button>
                    </CardFooter>
                )}
            </Card>

            <ApiKeyCreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
        </>
    )
}

export default ApiKeysCard
