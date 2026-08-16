import {
    retryInstallation,
    serverQueries,
    useServer,
    useServerDeployment,
    useTemplateGroups,
} from '@/features/servers/detail/api.ts'
import {
    Deployment,
    DeploymentStatus,
    DeploymentStep,
    ProgressMode,
} from '@/features/servers/types'
import { Server } from '@/types/server'
import { cn } from '@/utils'
import { IconAlertTriangle, IconChevronRight } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import byteSize from 'byte-size'
import { formatDistanceToNowStrict } from 'date-fns'
import { useEffect, useMemo, useRef, useState } from 'react'

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { LinearProgressBar } from '@/components/ui/Progress'
import Skeleton from '@/components/ui/Skeleton'
import { toast } from '@/components/ui/Toast'

import DeploymentStepRow, {
    formatStepDuration,
    stepIsBytes,
    stepLabel,
} from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

const bytes = (value: number) => byteSize(value, { units: 'iec' }).toString()

const InstallingServer = ({ server }: InstallingServerProps) => {
    const { data: fetchedDeployment } = useServerDeployment(server?.uuid, {
        refetchInterval: server?.lifecycle === 'install_failed' ? false : 250,
        // React Query skips interval refetches while the document is hidden,
        // and this app turns `refetchOnWindowFocus` off globally — so a tab
        // left on this screen stops polling the moment you switch away and has
        // nothing to bring it back when you return. An install outlasts most
        // people's attention, so both queries below opt back in.
        refetchOnWindowFocus: true,
    })
    // Cached from the rebuild page in the common case. The deployment carries
    // the template but not its group, so the family name is recovered by
    // finding the group that lists this template.
    const { data: templateGroups } = useTemplateGroups(server?.uuid)
    const queryClient = useQueryClient()

    /**
     * The server record is what decides when this screen goes away, so poll it
     * for as long as the screen is up. A second observer on the same query is
     * all this is: the layout above reads the same cache entry and swaps the
     * screen out on its own once the lifecycle moves off `installing`, and the
     * polling stops with the unmount.
     *
     * This used to be an effect keyed on `[deployment, lifecycle]` that
     * invalidated the record whenever the deployment came back null. Both of
     * those settle: the endpoint 204s (null) the moment nothing is active, and
     * the lifecycle it is waiting on is exactly the thing that cannot change
     * without a refetch — so the effect fired once, learned nothing, and never
     * ran again. That is what stranded this screen after the install had
     * already finished, and what left it on "Fetching progress…" when the
     * deployment row was still inside the reinstall request's transaction.
     */
    useServer(server?.uuid, {
        refetchInterval: 2_000,
        refetchOnWindowFocus: true,
    })

    const refetchServer = () => {
        if (server?.uuid) {
            queryClient.invalidateQueries({
                queryKey: serverQueries.detail(server.uuid).queryKey,
            })
        }
    }

    /**
     * `getDeployment` is scoped to `nonCompleted()`, so the instant the last
     * step lands the endpoint 204s and the query goes null — while this screen
     * is still up, because the server record it leaves on has not been polled
     * yet. Letting that null through emptied the card of everything it had
     * just been showing: the finish, the one moment worth watching, was the
     * one moment with nothing on screen.
     *
     * So the last deployment we were given is kept. It is not stale data being
     * passed off as live — the deployment and the server's `lifecycle` are
     * written in one transaction (ManagesDeploymentLifecycle::onComplete), so
     * a null here means those steps are final, and this screen is about to be
     * replaced anyway.
     */
    const lastDeployment = useRef<Deployment | null>(null)
    if (fetchedDeployment) lastDeployment.current = fetchedDeployment
    const deployment = fetchedDeployment ?? lastDeployment.current

    /**
     * And because those two rows commit together, the deployment turning null
     * *is* word that the lifecycle has moved — so ask for the record straight
     * away rather than waiting out the poll above. The poll stays as the
     * backstop; this only shortens the wait to a single round trip.
     */
    const wasFetched = useRef(false)
    useEffect(() => {
        if (wasFetched.current && fetchedDeployment === null) refetchServer()

        wasFetched.current = fetchedDeployment != null
    }, [fetchedDeployment])

    // Only ticks while a step is running, and only to age the "for 4.2s" line —
    // the steps themselves are repainted by the 250ms deployment poll.
    const [now, setNow] = useState(new Date())

    const steps = useMemo(
        () =>
            [...(deployment?.steps ?? [])].sort(
                (a, b) => a.sequence - b.sequence
            ),
        [deployment]
    )

    const runningStep = steps.find(
        step => step.status === DeploymentStatus.Running
    )
    const failedStep = steps.find(
        step => step.status === DeploymentStatus.Failed
    )
    const completedCount = steps.filter(
        step => step.status === DeploymentStatus.Completed
    ).length

    useEffect(() => {
        if (!runningStep) return

        const interval = setInterval(() => setNow(new Date()), 100)

        return () => clearInterval(interval)
    }, [!runningStep])

    const isFailed = server?.lifecycle === 'install_failed'
    const isDeleting = server?.lifecycle === 'deleting'
    const isRestoring = server?.lifecycle === 'restoring_backup'

    const templateName = deployment?.template?.name
    const templateFamily = templateGroups?.find(group =>
        group.templates?.some(
            template => template.uuid === deployment?.template?.uuid
        )
    )?.name
    const imageLabel = templateName
        ? [templateFamily, templateName].filter(Boolean).join(' ')
        : null

    const title = () => {
        if (isDeleting) return 'Deleting server'
        if (isRestoring) return 'Restoring backup'
        if (isFailed)
            return imageLabel
                ? `Could not install ${imageLabel}`
                : 'Installation failed'
        return imageLabel ? `Installing ${imageLabel}` : 'Installing server'
    }

    const subtitle = () => {
        if (!deployment) return 'Waiting for the node to pick it up'
        if (isFailed)
            return 'The rebuild stopped. Your disk was left as the failed step found it.'
        if (deployment.requestedAt)
            return `Started ${formatDistanceToNowStrict(deployment.requestedAt)} ago`
        return null
    }

    // Derived, not a field: finished steps plus how far the running one has
    // got. An indeterminate step contributes nothing until it completes — a
    // bar that invents a position is worse than one that waits.
    const stepFraction = (step?: DeploymentStep) =>
        step &&
        step.progressMode === ProgressMode.Determinate &&
        step.progressTotal > 0
            ? step.progressCurrent / step.progressTotal
            : 0

    const overallPercent = steps.length
        ? Math.round(
              ((completedCount + stepFraction(runningStep)) / steps.length) *
                  100
          )
        : 0

    // The headline reads the running step where there is one, the step that
    // stopped everything where one did, and otherwise the next step up — the
    // chain is queued but the node has not started on it, which is where every
    // install begins and where it read "Finishing up" before.
    const allComplete = steps.length > 0 && completedCount === steps.length
    const headlineStep =
        runningStep ??
        failedStep ??
        steps.find(step => step.status === DeploymentStatus.Pending)
    const headlinePercent = () => {
        if (isFailed) return 'Failed'
        if (allComplete) return '100%'
        if (
            runningStep &&
            runningStep.progressMode === ProgressMode.Determinate &&
            runningStep.progressTotal > 0
        ) {
            return `${Math.round(stepFraction(runningStep) * 100)}%`
        }
        return `${overallPercent}%`
    }

    const headlineDetail = () => {
        if (isFailed) return 'Nothing after this step ran'
        if (allComplete)
            return deployment?.startOnCompletion
                ? 'Starting the server'
                : 'The server is ready and stays powered off'
        if (!runningStep) return 'Waiting for the node to pick it up'
        if (
            runningStep.progressTotal > 0 &&
            stepIsBytes(runningStep.name) &&
            runningStep.progressMode === ProgressMode.Determinate
        ) {
            return `${bytes(runningStep.progressCurrent)} of ${bytes(runningStep.progressTotal)}`
        }
        if (runningStep.startedAt) {
            return `Running for ${formatStepDuration(runningStep.startedAt, now)}`
        }
        return null
    }

    const remaining = headlineStep
        ? steps.length - steps.indexOf(headlineStep) - 1
        : 0

    const handleRetry = async () => {
        if (!server?.uuid) return
        try {
            await retryInstallation(server.uuid)
            refetchServer()
        } catch (error) {
            toast.add({ title: 'Failed to retry installation.', type: 'error' })
        }
    }

    return (
        <div
            className={
                'flex h-full min-h-[50vh] flex-col items-center justify-center p-4'
            }
        >
            <Card className={'w-full max-w-lg'}>
                <CardHeader>
                    <CardTitle as={'h1'}>{title()}</CardTitle>
                    <CardDescription>{subtitle()}</CardDescription>
                </CardHeader>

                <CardContent className={'flex flex-col gap-4'}>
                    {isFailed && (
                        <Alert variant={'destructive'}>
                            <IconAlertTriangle className={'size-4'} />
                            <AlertTitle>
                                {failedStep
                                    ? `${stepLabel(failedStep.name)} failed`
                                    : 'The installation failed'}
                            </AlertTitle>
                            <AlertDescription>
                                {failedStep?.errorCode && (
                                    <span className={'font-mono font-semibold'}>
                                        {failedStep.errorCode}
                                        {failedStep.errorMessage ? ' — ' : ''}
                                    </span>
                                )}
                                {failedStep?.errorMessage ??
                                    (failedStep?.errorCode
                                        ? null
                                        : 'The node did not say why. Retry, and contact your provider if it fails again.')}
                            </AlertDescription>
                        </Alert>
                    )}

                    {steps.length > 0 && (
                        <>
                            <div className={'flex flex-col gap-3'}>
                                <div
                                    className={
                                        'flex items-baseline justify-between gap-4'
                                    }
                                >
                                    <span className={'text-lg font-medium'}>
                                        {allComplete
                                            ? 'All steps finished'
                                            : headlineStep
                                              ? stepLabel(headlineStep.name)
                                              : 'Finishing up'}
                                    </span>
                                    <span
                                        className={cn(
                                            'text-lg font-medium tabular-nums',
                                            isFailed
                                                ? 'text-destructive'
                                                : 'text-muted-foreground'
                                        )}
                                    >
                                        {headlinePercent()}
                                    </span>
                                </div>

                                {/* One pip per step: the shape of the run at a
                                    glance, without reading the list. */}
                                <div className={'flex items-center gap-1.5'}>
                                    {steps.map(step => (
                                        <LinearProgressBar
                                            key={step.id}
                                            value={
                                                step.status ===
                                                    DeploymentStatus.Completed ||
                                                step.status ===
                                                    DeploymentStatus.Failed
                                                    ? 100
                                                    : Math.round(
                                                          stepFraction(step) *
                                                              100
                                                      )
                                            }
                                            className={'bg-muted h-1 flex-1'}
                                            indicatorClassName={cn(
                                                step.status ===
                                                    DeploymentStatus.Completed &&
                                                    'bg-success',
                                                step.status ===
                                                    DeploymentStatus.Failed &&
                                                    'bg-destructive'
                                            )}
                                        />
                                    ))}
                                </div>

                                <div
                                    className={
                                        'text-muted-foreground flex justify-between gap-4 text-sm tabular-nums'
                                    }
                                >
                                    <span>{headlineDetail()}</span>
                                    {/* Only a claim about what is left when
                                        something is actually left. */}
                                    {!allComplete && (
                                        <span>
                                            {remaining === 0
                                                ? 'Last step'
                                                : `${remaining} step${remaining === 1 ? '' : 's'} after this`}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <details
                                className={'group/steps border-t pt-3'}
                                open={isFailed}
                            >
                                <summary
                                    className={
                                        'text-muted-foreground hover:text-foreground flex cursor-pointer list-none items-center gap-1 text-sm [&::-webkit-details-marker]:hidden'
                                    }
                                >
                                    <IconChevronRight
                                        className={
                                            'size-3.5 transition-transform group-open/steps:rotate-90'
                                        }
                                    />
                                    All {steps.length} steps
                                </summary>
                                <ol className={'flex flex-col pt-2'}>
                                    {steps.map((step, index) => (
                                        <DeploymentStepRow
                                            key={step.id}
                                            step={step}
                                            isLast={index === steps.length - 1}
                                        />
                                    ))}
                                </ol>
                            </details>
                        </>
                    )}

                    {/* Nothing to report yet: the deployment row is still
                        inside the reinstall request's transaction, or the node
                        has not picked the chain up. Hold the loaded card's
                        shape rather than collapsing to a line of grey text, so
                        the first step landing changes the contents and not the
                        layout. The sliver crosses the whole row — one pass
                        over four segments, since the real step count is one of
                        the things not known yet. */}
                    {steps.length === 0 && (
                        <div className={'flex flex-col gap-3'}>
                            <div
                                className={
                                    'flex items-center justify-between gap-4'
                                }
                            >
                                <Skeleton className={'h-5 w-36'} />
                                <Skeleton className={'h-5 w-10'} />
                            </div>
                            <div
                                className={'relative flex items-center gap-1.5'}
                            >
                                {[0, 1, 2, 3].map(index => (
                                    <span
                                        key={index}
                                        className={
                                            'bg-muted h-1 flex-1 rounded-full'
                                        }
                                    />
                                ))}
                                {/* Full-row layer so the mask's percentages
                                    resolve against the row, and so the sliver
                                    inside it travels the whole way at one
                                    width while the gaps stay gaps. */}
                                <span
                                    aria-hidden
                                    className={
                                        'pip-sweep-mask pointer-events-none absolute inset-0 overflow-hidden'
                                    }
                                >
                                    <span
                                        className={
                                            'bg-primary animate-pip-sweep absolute top-0 h-1 w-[20%] rounded-full'
                                        }
                                    />
                                </span>
                            </div>
                            <div
                                className={
                                    'flex items-center justify-between gap-4'
                                }
                            >
                                <Skeleton className={'h-3.5 w-28'} />
                                <Skeleton className={'h-3.5 w-20'} />
                            </div>
                        </div>
                    )}

                    {!isFailed && (
                        <p className={'text-muted-foreground text-sm'}>
                            You can leave this page. The work runs on the node,
                            and this screen picks it back up.
                        </p>
                    )}
                </CardContent>

                {isFailed && (
                    <CardFooter className={'justify-end'}>
                        <Button onClick={handleRetry} variant={'destructive'}>
                            Retry installation
                        </Button>
                    </CardFooter>
                )}
            </Card>
        </div>
    )
}

export default InstallingServer
