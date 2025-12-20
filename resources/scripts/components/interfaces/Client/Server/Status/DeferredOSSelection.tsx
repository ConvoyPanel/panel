import { Server } from '@/types/server'

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/Card'
import OSSelectionForm from '@/components/interfaces/Client/Server/Rebuild/OSSelectionForm'

interface DeferredOSSelectionProps {
    server?: Server
}

export default function DeferredOSSelection({ server }: DeferredOSSelectionProps) {
    if (!server) return null

    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-lg'>
                <CardHeader>
                    <CardTitle>Operating System Selection</CardTitle>
                    <CardDescription>
                        Choose an operating system to install on your server.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <OSSelectionForm server={server} />
                </CardContent>
            </Card>
        </div>
    )
}
