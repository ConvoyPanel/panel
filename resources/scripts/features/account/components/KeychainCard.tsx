import SSHKey from '@/features/account/components/SSHKey.tsx'
import SSHKeyCreateDialog from '@/features/account/components/SSHKeyCreateDialog.tsx'
import {
    deleteSSHKey,
    sshKeyQueries,
    useSSHKeys,
} from '@/features/account/ssh-keys/api.ts'
import { SSHKey as SSHKeyType } from '@/features/account/types.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'
import { IconKey } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import ConfirmWithAuthDialog from '@/components/ui/Dialog/ConfirmWithAuthDialog.tsx'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { OverflowItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

const KeychainCard = () => {
    const mutate = useQueryMutator<SSHKeyType[]>(sshKeyQueries.all())
    const [createOpen, setCreateOpen] = useState(false)
    const [keyToRemove, setKeyToRemove] = useState<SSHKeyType | null>(null)

    const { data: keys, isLoading, isError, refetch } = useSSHKeys()

    const { mutateAsync: remove } = useMutation({
        mutationFn: (key: SSHKeyType) => deleteSSHKey(key.id),
        onSuccess: (_, key) => {
            mutate(keys => keys?.filter(k => k.id !== key.id))
            toast.add({ title: 'SSH key removed', type: 'success' })
        },
        onError: () =>
            toast.add({ title: 'Failed to remove key', type: 'error' }),
    })

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>SSH Keychain</CardTitle>
                    <CardDescription>
                        Manage your SSH public keys
                    </CardDescription>
                    {keys && keys.length > 0 && (
                        <CardAction>
                            <Button
                                variant={'outline'}
                                onClick={() => setCreateOpen(true)}
                            >
                                Add Key
                            </Button>
                        </CardAction>
                    )}
                </CardHeader>
                <CardContent
                    className={cn(
                        'flex-1',
                        isLoading || isError || keys?.length === 0
                            ? 'grid min-h-[12rem] place-items-center'
                            : 'flex flex-col'
                    )}
                >
                    {isError && !keys ? (
                        <CollectionErrorState onRetry={refetch} />
                    ) : isLoading || !keys ? (
                        <Skeleton className={'h-40 w-full'} />
                    ) : keys.length > 0 ? (
                        <OverflowItemGroup
                            title={'SSH Keychain'}
                            rows={keys.map(key => (
                                <SSHKey
                                    key={key.id}
                                    publicKey={key}
                                    onDelete={setKeyToRemove}
                                />
                            ))}
                        />
                    ) : (
                        <SimpleEmptyState
                            icon={IconKey}
                            title={'SSH Keychain'}
                            description={'You have no keys in your keychain.'}
                            action={
                                <Button onClick={() => setCreateOpen(true)}>
                                    Add Key
                                </Button>
                            }
                        />
                    )}
                </CardContent>
            </Card>

            <SSHKeyCreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />

            {/* Removal is behind RequireIdentityConfirmation too, so it needs
                the gate the plain confirm() alert could not carry. */}
            <ConfirmWithAuthDialog
                subject={keyToRemove}
                onClose={() => setKeyToRemove(null)}
                title={'Remove SSH key'}
                description={key =>
                    `“${key.name}” will be removed from your keychain. Servers it was already added to are unaffected.`
                }
                confirmText={'Remove'}
                onConfirm={remove}
            />
        </>
    )
}

export default KeychainCard
