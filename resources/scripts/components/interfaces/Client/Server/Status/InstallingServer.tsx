import { useEffect } from 'react'
import { DeploymentStep } from '@/types/deployment'
import { Server } from '@/types/server'
import { IconRefresh } from '@tabler/icons-react'

import useServerDeploymentSWR from '@/api/servers/use-server-deployment-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

import DeploymentStepRow from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

export default function InstallingServer({ server }: InstallingServerProps) {
    const { data: deployment } = useServerDeploymentSWR(server?.uuid, {
        refreshInterval: 250,
        dedupingInterval: 0,
    })
    const { mutate } = useServerSWR()

    useEffect(() => {
        if (deployment === null) {
            mutate()
        }
    }, [deployment, mutate])

    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-lg'>
                <CardHeader className={'px-2.5 py-4'}>
                    <div className={'flex items-center space-x-2'}>
                        <div className={'animate-spin-ease'}>
                            <IconRefresh stroke={1} className={'size-12 -scale-x-100'} />
                        </div>
                        <div>
                            <CardTitle className={'text-xl font-normal'}>Server Installing</CardTitle>
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
                            Loading installation progress...
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    )
}
