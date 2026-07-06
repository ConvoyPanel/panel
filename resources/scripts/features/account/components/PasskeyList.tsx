import { IconKey } from '@tabler/icons-react'

import { usePasskeys } from '@/features/account/passkeys/api.ts'

import Passkey from '@/features/account/components/Passkey.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'

import SimpleEmptyState from '@/components/ui/EmptyStates/SimpleEmptyState.tsx'

const PasskeyList = () => {
    const { data, isLoading } = usePasskeys()

    if (isLoading) {
        return <Skeleton className={'h-24 w-full'} />
    }

    if (!data || data?.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconKey}
                title={'No passkeys'}
                description={'You have not added any passkeys to your account.'}
            />
        )
    }

    return (
        <ul className={'flex flex-col divide-y divide-accent'}>
            {data.map(passkey => (
                <li key={passkey.id}>
                    <Passkey passkey={passkey} />
                </li>
            ))}
        </ul>
    )
}

export default PasskeyList
