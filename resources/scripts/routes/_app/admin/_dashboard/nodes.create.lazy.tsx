import {
    createEnrollmentKey,
    enrollmentQueries,
    useEnrollments,
} from '@/features/anchors/api'
import EnrollmentPanel from '@/features/anchors/components/EnrollmentPanel.tsx'
import { IconArrowRight, IconServer } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import { buttonVariants } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import FormToolbar from '@/components/ui/FormToolbar'
import { StatLabel } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes/create')(
    { component: EnrollNodePage }
)

/**
 * Adding a node, inverted.
 *
 * There is no form here because there is nothing to fill in: every field the
 * old one asked for is something the host already knows, and the two that were
 * not — location and oversubscription — are asked at approval, once there is a
 * real machine to attach them to.
 *
 * The key is single-use and short-lived by default. A reusable one for a whole
 * rack is a deliberate act, made from the enrollment-keys screen rather than
 * arrived at by leaving a field alone here.
 */
function EnrollNodePage() {
    // Issued once per visit, when the panel below mounts.
    const [issuedAt] = useState(() => new Date().toISOString())

    const { data } = useEnrollments({})
    const waiting = data?.items ?? []
    // Only machines that turned up after this page was opened: an operator
    // enrolling a second host must not be handed the first one to approve.
    const arrived = waiting.filter(
        item => item.enrolledAt !== null && item.enrolledAt > issuedAt
    )

    return (
        <div className={'mx-auto w-full max-w-3xl'}>
            <FormToolbar
                title={'Enroll a node'}
                actions={
                    <Link
                        to={'/admin/nodes'}
                        className={buttonVariants({ variant: 'ghost' })}
                    >
                        Cancel
                    </Link>
                }
            />

            <div className={'space-y-4 pt-4'}>
                <Card>
                    <CardHeader>
                        <CardTitle>Install command</CardTitle>
                        <StatLabel>Run as root on the Proxmox host.</StatLabel>
                    </CardHeader>
                    <EnrollmentPanel
                        subject={issuedAt}
                        done={arrived.length > 0}
                        issueToken={async () => {
                            const key = await createEnrollmentKey({
                                name: `Node enrollment ${new Date().toLocaleString()}`,
                                mode: 'agent',
                                maxUses: '1',
                                expiresInMinutes: '15',
                            })

                            // The panel wants the command and a countdown; a key
                            // hands back both under the same names.
                            return {
                                token: key.token as string,
                                command: key.command as string,
                                expiresAt: key.expiresAt as string,
                            }
                        }}
                        refresh={() =>
                            queryClient.invalidateQueries({
                                queryKey: enrollmentQueries.all(),
                            })
                        }
                    />
                </Card>

                {arrived.map(item => (
                    <Card key={item.id}>
                        <CardContent
                            className={
                                'flex flex-wrap items-center justify-between gap-3 py-4'
                            }
                        >
                            <div className={'flex items-center gap-3'}>
                                <IconServer
                                    className={'text-muted-foreground size-5'}
                                />
                                <div className={'font-medium'}>{item.name}</div>
                            </div>
                            <Link
                                to={'/admin/anchors/$anchorId'}
                                params={{ anchorId: String(item.id) }}
                                className={buttonVariants({
                                    variant: 'default',
                                })}
                            >
                                Review and approve{' '}
                                <IconArrowRight className={'size-4'} />
                            </Link>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    )
}
