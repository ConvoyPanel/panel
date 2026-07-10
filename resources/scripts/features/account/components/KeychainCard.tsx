import { useMutation } from '@tanstack/react-query'
import { IconKey } from '@tabler/icons-react'
import { useState } from 'react'
import { toast } from 'sonner'

import {
    deleteSSHKey,
    sshKeyQueries,
    useSSHKeys,
} from '@/features/account/ssh-keys/api.ts'
import { SSHKey as SSHKeyType } from '@/features/account/types.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'

import SSHKey from '@/features/account/components/SSHKey.tsx'
import SSHKeyCreateDialog from '@/features/account/components/SSHKeyCreateDialog.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { OverflowItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const KeychainCard = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const mutate = useQueryMutator<SSHKeyType[]>(sshKeyQueries.all())
    const [createOpen, setCreateOpen] = useState(false)

    const { data: keys, isLoading } = useSSHKeys()

    const { mutate: remove } = useMutation({
        mutationFn: (key: SSHKeyType) => deleteSSHKey(key.id),
        onSuccess: (_, key) => {
            mutate(keys => keys?.filter(k => k.id !== key.id))
            toast.success('SSH key removed')
        },
        onError: () => toast.error('Failed to remove key'),
    })

    const handleDelete = async (key: SSHKeyType) => {
        const confirmed = await confirm({
            title: 'Remove SSH key',
            description: `“${key.name}” will be removed from your keychain. Servers it was already added to are unaffected.`,
        })
        if (!confirmed) return

        remove(key)
    }

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
                                size={'sm'}
                                onClick={() => setCreateOpen(true)}
                            >
                                Add Key
                            </Button>
                        </CardAction>
                    )}
                </CardHeader>
                <CardContent
                    className={cn(
                        (isLoading || keys?.length === 0) &&
                            'grid min-h-[12rem] place-items-center'
                    )}
                >
                    {isLoading || !keys ? (
                        <Skeleton className={'h-40 w-full'} />
                    ) : keys.length > 0 ? (
                        <OverflowItemGroup
                            title={'SSH Keychain'}
                            rows={keys.map(key => (
                                <SSHKey
                                    key={key.id}
                                    publicKey={key}
                                    onDelete={handleDelete}
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

            <SSHKeyCreateDialog open={createOpen} onOpenChange={setCreateOpen} />
        </>
    )
}

export default KeychainCard
