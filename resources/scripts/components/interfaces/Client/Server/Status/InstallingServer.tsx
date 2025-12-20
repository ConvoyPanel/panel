import { DeploymentStep } from '@/types/deployment'
import { Server } from '@/types/server'
import { IconLoader } from '@tabler/icons-react'

import useServerDeploymentSWR from '@/api/servers/use-server-deployment-swr.ts'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

import DeploymentStepRow from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

export default function InstallingServer({ server }: InstallingServerProps) {
    const { data: deployment } = useServerDeploymentSWR(server?.uuid, {
        refreshInterval: 1000,
    })

    console.log({ deployment })

    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-lg'>
                <CardHeader className='text-center'>
                    <div className='mx-auto mb-4 w-fit rounded-full bg-blue-100 p-3 dark:bg-blue-900/30'>
                        <IconLoader className='h-8 w-8 animate-spin text-blue-600 dark:text-blue-400' />
                    </div>
                    <CardTitle>Server Installing</CardTitle>
                    <CardDescription>
                        Your server is currently being installed.
                    </CardDescription>
                </CardHeader>
                <CardContent className='space-y-4'>
                    {deployment ? (
                        deployment.steps.map((step: DeploymentStep) => (
                            <DeploymentStepRow key={step.id} step={step} />
                        ))
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
