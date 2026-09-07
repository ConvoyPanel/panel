import type { AdminUserDetail } from '@/types/admin/user.ts'
import { format, formatDistanceToNow } from 'date-fns'
import { ReactNode } from 'react'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    user: AdminUserDetail | undefined
}

const Row = ({ label, children }: { label: string; children: ReactNode }) => (
    <div className={'flex items-baseline justify-between gap-4'}>
        <dt className={'text-muted-foreground text-xs'}>{label}</dt>
        <dd className={'min-w-0 truncate text-right tabular-nums'}>
            {children}
        </dd>
    </div>
)

const none = <span className={'text-muted-foreground'}>&mdash;</span>

/**
 * Eight facts as a definition list rather than eight tiles. Density is the point: this is the card
 * an operator reads while someone is on the phone.
 */
const AccountCard = ({ user }: Props) => (
    <Card>
        <CardHeader>
            <CardTitle>Account</CardTitle>
            <CardDescription>Who this is, and how they sign in.</CardDescription>
        </CardHeader>
        <CardContent className={'flex-1'}>
            {!user ? (
                <Skeleton className={'h-48 w-full'} />
            ) : (
                <dl className={'flex flex-col gap-2'}>
                    <Row label={'User ID'}>{user.id}</Row>
                    <Row label={'Role'}>
                        {user.rootAdmin ? 'Administrator' : 'User'}
                    </Row>
                    <Row label={'Created'}>
                        {user.createdAt
                            ? format(new Date(user.createdAt), 'PP')
                            : none}
                    </Row>
                    <Row label={'Last sign-in'}>
                        {user.lastLoginAt ? (
                            <>
                                {formatDistanceToNow(new Date(user.lastLoginAt), {
                                    addSuffix: true,
                                })}
                                {user.lastLoginIp && (
                                    <span className={'text-muted-foreground'}>
                                        {' '}
                                        &middot; {user.lastLoginIp}
                                    </span>
                                )}
                            </>
                        ) : (
                            /* Not "never": the audit log is pruned on a window, so a very old
                               sign-in is indistinguishable from none at all. */
                            <span className={'text-muted-foreground'}>
                                Not recorded
                            </span>
                        )}
                    </Row>
                    <Row label={'Two-factor'}>
                        {user.twoFactorEnabled
                            ? 'Authenticator app'
                            : user.passkeysCount > 0
                              ? 'Passkey only'
                              : 'Off'}
                    </Row>
                    <Row label={'Passkeys'}>{user.passkeysCount}</Row>
                    <Row label={'SSH keys'}>{user.sshKeysCount}</Row>
                    <Row label={'API keys'}>{user.apiKeysCount}</Row>
                    <Row label={'Linked logins'}>
                        {user.oauthConnectionsCount}
                    </Row>
                </dl>
            )}
        </CardContent>
    </Card>
)

export default AccountCard
