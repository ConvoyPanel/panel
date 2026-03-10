import { DeploymentStatus, DeploymentStep } from '@/types/deployment'
import { Server } from '@/types/server'
import { IconAlertTriangle, IconRefresh } from '@tabler/icons-react'
import { useEffect } from 'react'
import { toast } from 'sonner'

import retryInstallation from '@/api/servers/retryInstallation'
import useServerDeploymentSWR from '@/api/servers/use-server-deployment-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

import DeploymentStepRow from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

export default function InstallingServer({ server }: InstallingServerProps) {
    const { data: deployment } = useServerDeploymentSWR(server?.uuid, {
        refreshInterval: server?.status === 'install_failed' ? 0 : 250,
        dedupingInterval: 0,
    })
    const { mutate } = useServerSWR()

    useEffect(() => {
        // If deployment is null and we are not failed, maybe it finished?
        // But if failed, we should still have deployment data (from backend change).
        if (deployment === null && server?.status !== 'install_failed') {
            mutate()
        }

        if (
            deployment?.status === DeploymentStatus.Failed &&
            server?.status !== 'install_failed'
        ) {
            mutate()
        }
    }, [deployment, mutate, server?.status])

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
            mutate()
        } catch (error) {
            toast.error('Failed to retry installation.')
        }
    }

    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-lg'>
                <CardHeader className={'px-2.5 py-4'}>
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
                            <CardTitle className={'text-xl font-normal'}>
                                {getTitle()}
                            </CardTitle>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className='px-2 pb-2'>
                    {deployment ? (
                        <ul className='divide-y overflow-hidden rounded-md border'>
                            {deployment.steps.map((step: DeploymentStep) => (
                                <DeploymentStepRow key={step.id} step={step} />
                            ))}
                        </ul>
                    ) : (
                        <p className='text-center text-sm text-muted-foreground'>
                            {getLoadingText()}
                        </p>
                    )}
                </CardContent>
                {isFailed && (
                    <CardFooter className='flex justify-end px-2 pb-2 pt-0'>
                        <Button onClick={handleRetry} variant='destructive'>
                            Retry Installation
                        </Button>
                    </CardFooter>
                )}
            </Card>
        </div>
    )
}
