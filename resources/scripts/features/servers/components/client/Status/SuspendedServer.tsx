import { IconAlertTriangle } from '@tabler/icons-react'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

export default function SuspendedServer() {
    return (
        <div className='flex h-full min-h-[50vh] flex-col items-center justify-center p-4'>
            <Card className='w-full max-w-md border-red-200 dark:border-red-900/50'>
                <CardHeader className='text-center'>
                    <div className='mx-auto mb-4 w-fit rounded-full bg-red-100 p-3 dark:bg-red-900/30'>
                        <IconAlertTriangle className='h-8 w-8 text-red-600 dark:text-red-400' />
                    </div>
                    <CardTitle
                        as='h1'
                        className='text-red-700 dark:text-red-400'
                    >
                        Server Suspended
                    </CardTitle>
                    <CardDescription>
                        This server has been suspended. Access is restricted.
                    </CardDescription>
                </CardHeader>
                <CardContent className='flex justify-center'>
                    <Button variant='outline' asChild>
                        <a href='#' onClick={e => e.preventDefault()}>
                            Contact Support
                        </a>
                    </Button>
                </CardContent>
            </Card>
        </div>
    )
}
