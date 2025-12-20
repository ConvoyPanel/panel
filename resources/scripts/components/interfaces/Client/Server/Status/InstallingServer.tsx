import { IconLoader } from '@tabler/icons-react'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'
import { Server } from '@/types/server'

import useServerDeploymentSWR from '@/api/servers/use-server-deployment-swr.ts'
import DeploymentStepRow from './DeploymentStepRow.tsx'

interface InstallingServerProps {
    server?: Server
}

export default function InstallingServer({ server }: InstallingServerProps) {
    const { data: deployment } = useServerDeploymentSWR(server?.uuid, {
        refreshInterval: 1000,
    })

    return (
        <div className="flex flex-col items-center justify-center h-full min-h-[50vh] p-4">
            <Card className="w-full max-w-lg">
                <CardHeader className="text-center">
                    <div className="mx-auto bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full w-fit mb-4">
                        <IconLoader className="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    </div>
                    <CardTitle>Server Installing</CardTitle>
                    <CardDescription>
                        Your server is currently being installed.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {deployment?.steps.map((step) => (
                        <DeploymentStepRow key={step.id} step={step} />
                    ))}
                    {!deployment && (
                         <p className="text-sm text-center text-muted-foreground">
                            Loading installation progress...
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    )
}
