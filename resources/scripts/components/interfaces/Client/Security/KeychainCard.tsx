import { SSHKey as Key } from '@/types/keychain.ts'
import { cn } from '@/utils'
import { IconKey } from '@tabler/icons-react'

import SSHKey from '@/components/interfaces/Client/Security/SSHKey.tsx'

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


const KeychainCard = () => {
    const keys: Key[] = [
        {
            id: 1,
            name: 'Key 1',
            createdAt: new Date(),
        },
        {
            id: 2,
            name: 'Key 2',
            createdAt: new Date(),
        },
    ]

    return (
        <Card>
            <CardHeader>
                <CardTitle>SSH Keychain</CardTitle>
                <CardDescription>Manage your SSH public keys </CardDescription>
            </CardHeader>
            <CardContent
                className={cn(
                    'min-h-[14rem]',
                    keys.length === 0 && 'grid place-items-center'
                )}
            >
                {keys.length > 0 ? (
                    <ul className={'space-y-1'}>
                        {keys.map(key => (
                            <li key={key.id}>
                                <SSHKey publicKey={key} />
                            </li>
                        ))}
                    </ul>
                ) : (
                    <SimpleEmptyState
                        icon={IconKey}
                        title={'SSH Keychain'}
                        description={'You have no keys in your keychain.'}
                        action={<Button>Add Key</Button>}
                    />
                )}
            </CardContent>
            {keys.length > 0 && (
                <CardFooter className={'flex justify-end space-x-3'}>
                    <p className={'text-sm text-muted-foreground'}>
                        3 keys remaining
                    </p>
                    <Button>Add Key</Button>
                </CardFooter>
            )}
        </Card>
    )
}

export default KeychainCard
