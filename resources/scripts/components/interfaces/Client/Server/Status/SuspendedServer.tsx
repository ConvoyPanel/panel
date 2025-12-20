import { IconAlertTriangle } from '@tabler/icons-react'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'

export default function SuspendedServer() {
    return (
        <div className="flex flex-col items-center justify-center h-full min-h-[50vh] p-4">
            <Card className="w-full max-w-md border-red-200 dark:border-red-900/50">
                <CardHeader className="text-center">
                    <div className="mx-auto bg-red-100 dark:bg-red-900/30 p-3 rounded-full w-fit mb-4">
                        <IconAlertTriangle className="w-8 h-8 text-red-600 dark:text-red-400" />
                    </div>
                    <CardTitle className="text-red-700 dark:text-red-400">Server Suspended</CardTitle>
                    <CardDescription>
                        This server has been suspended. Access is restricted.
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex justify-center">
                    <Button variant="outline" asChild>
                        <a href="#" onClick={(e) => e.preventDefault()}>Contact Support</a>
                    </Button>
                </CardContent>
            </Card>
        </div>
    )
}
