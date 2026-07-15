import {
    retryInstallation,
    serverQueries,
    useServerDeployment,
} from '@/features/servers/detail/api.ts'
import { DeploymentStatus, DeploymentStep } from '@/features/servers/types'
import { Server } from '@/types/server'
import { IconAlertTriangle, IconRefresh } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { toast } from 'sonner'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { ItemGroup } from '@/components/ui/Item'

import DeploymentStepRow from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

export default function InstallingServer({ server }: InstallingServerProps) {
    const { data: deployment } = useServerDeployment(server?.uuid, {
        refetchInterval: server?.status === 'install_failed' ? false : 250,
    })
    const queryClient = useQueryClient()

    const refetchServer = () => {
        if (server?.uuid) {
            queryClient.invalidateQueries({
                queryKey: serverQueries.detail(server.uuid).queryKey,
            })
        }
    }

    useEffect(() => {
        // If deployment is null and we are not failed, maybe it finished?
        // But if failed, we should still have deployment data (from backend change).
        if (deployment === null && server?.status !== 'install_failed') {
            refetchServer()
        }

        if (
            deployment?.status === DeploymentStatus.Failed &&
            server?.status !== 'install_failed'
        ) {
            refetchServer()
        }
    }, [deployment, server?.status])

    const isFailed = server?.status === 'install_failed'

    const getTitle = () => {
        if (isFailed) return 'Installation Failed'
        if (server?.status === 'deleting') return 'Deleting Server'
        if (server?.status === 'restoring_backup') return 'Restoring Backup'
        return 'Server Installing'
    }

    const getLoadingText = () => {
        if (isFailed) return 'No deployment details available.'
        if (server?.status === 'deleting') return 'Loading deletion progress...'
        if (server?.status === 'restoring_backup')
            return 'Loading restore progress...'
        return 'Loading installation progress...'
    }

    const handleRetry = async () => {
        if (!server?.uuid) return
        try {
            await retryInstallation(server.uuid)
            refetchServer()
        } catch (error) {
            toast.error('Failed to retry installation.')
        }
    }

    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-lg'>
                <CardHeader>
                    <div className={'flex items-center space-x-2'}>
                        {isFailed ? (
                            <div className={'text-red-500'}>
                                <IconAlertTriangle
                                    stroke={1}
                                    className={'size-12'}
                                />
                            </div>
                        ) : (
                            <div className={'animate-spin-ease'}>
                                <IconRefresh
                                    stroke={1}
                                    className={'size-12 -scale-x-100'}
                                />
                            </div>
                        )}
                        <div>
                            <CardTitle
                                as={'h1'}
                                className={'text-xl font-normal'}
                            >
                                {getTitle()}
                            </CardTitle>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {deployment ? (
                        <ItemGroup className={'gap-3'}>
                            {deployment.steps.map((step: DeploymentStep) => (
                                <DeploymentStepRow key={step.id} step={step} />
                            ))}
                        </ItemGroup>
                    ) : (
                        <p className='text-muted-foreground text-center text-sm'>
                            {getLoadingText()}
                        </p>
                    )}
                </CardContent>
                {isFailed && (
                    <CardFooter className='justify-end'>
                        <Button onClick={handleRetry} variant='destructive'>
                            Retry Installation
                        </Button>
                    </CardFooter>
                )}
            </Card>
        </div>
    )
}
