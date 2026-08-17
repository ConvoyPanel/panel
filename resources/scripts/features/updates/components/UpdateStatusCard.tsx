import {
    checkForUpdates,
    updateQueries,
    useUpdateStatus,
} from '@/features/updates/api.ts'
import updateSummary, {
    lastChecked,
    releaseLine,
} from '@/features/updates/status.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import {
    IconCircleArrowUp,
    IconCircleCheck,
    IconExternalLink,
    IconRefresh,
} from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

/** One row of provenance: where a number in the left column came from. */
const Fact = ({ label, children }: { label: string; children: ReactNode }) => (
    <div className={'grid grid-cols-[7.5rem_minmax(0,1fr)] items-baseline gap-3'}>
        <dt className={'text-muted-foreground text-xs'}>{label}</dt>
        <dd className={'text-sm break-words tabular-nums'}>{children}</dd>
    </div>
)

const UpdateStatusCard = () => {
    const { data: status, isLoading } = useUpdateStatus()
    const mutateStatus = useQueryMutator(updateQueries.status().queryKey)

    const { mutate: check, isPending } = useMutation({
        mutationFn: checkForUpdates,
        onSuccess: async checked => {
            await mutateStatus(() => checked)
        },
        onError: error => {
            toast.add({
                title: getApiErrorMessage(
                    error,
                    'Could not reach GitHub to check for updates'
                ),
                type: 'error',
            })
        },
    })

    if (isLoading || !status) {
        return <Skeleton className={'h-64'} />
    }

    const summary = updateSummary(status)

    return (
        <Card>
            <CardHeader>
                <CardTitle>Panel version</CardTitle>
                <CardDescription className={'max-w-prose'}>
                    What this install is running, and where that verdict comes
                    from. Convoy only reports that a release exists — upgrading
                    is still something you run yourself.
                </CardDescription>
            </CardHeader>

            <CardContent
                // The version block only needs its own width; a fractional
                // column would push the provenance list into the middle of a
                // full-width card and read as two unrelated halves.
                className={'grid gap-6 @lg:grid-cols-[minmax(11rem,15rem)_1fr]'}
            >
                <div className={'flex flex-col items-start gap-2'}>
                    <div className={'flex items-center gap-2'}>
                        <span
                            className={
                                'text-3xl font-semibold tracking-tight tabular-nums'
                            }
                        >
                            {status.currentVersion}
                        </span>
                        {/* The mark carries the verdict now that the caption
                            below spells it out; nothing is shown when there is
                            no verdict to give. */}
                        {summary.mark === 'behind' && (
                            <IconCircleArrowUp
                                className={
                                    'size-5 text-amber-500 dark:text-amber-400'
                                }
                                role={'img'}
                                aria-label={summary.label}
                            />
                        )}
                        {summary.mark === 'current' && (
                            <IconCircleCheck
                                className={'text-success size-5'}
                                role={'img'}
                                aria-label={summary.label}
                            />
                        )}
                    </div>
                    <p className={'text-muted-foreground text-sm'}>
                        {summary.caption}
                    </p>
                    {status.releaseUrl && (
                        <Button
                            variant={
                                status.updateAvailable ? 'default' : 'outline'
                            }
                            className={'mt-1'}
                            asChild
                        >
                            <a
                                href={status.releaseUrl}
                                target={'_blank'}
                                rel={'noreferrer'}
                            >
                                Release notes
                                <IconExternalLink className={'size-4'} />
                            </a>
                        </Button>
                    )}
                </div>

                <dl className={'flex flex-col gap-2.5'}>
                    {/* Redundant when the number beside it is the same one. */}
                    {status.latestVersion &&
                        status.latestVersion !== status.currentVersion && (
                            <Fact label={'Latest release'}>
                                {releaseLine(status)}
                            </Fact>
                        )}
                    <Fact label={'Channel'}>Stable</Fact>
                    <Fact label={'Source'}>
                        <span className={'font-mono text-xs'}>
                            {status.repository}
                        </span>
                    </Fact>
                    <Fact label={'Last checked'}>{lastChecked(status)}</Fact>
                </dl>
            </CardContent>

            <CardFooter>
                <Button
                    variant={'outline'}
                    onClick={() => check()}
                    loading={isPending}
                    disabled={isPending}
                    icon={<IconRefresh className={'size-4'} />}
                >
                    Check now
                </Button>
            </CardFooter>
        </Card>
    )
}

export default UpdateStatusCard
