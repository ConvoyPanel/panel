import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

const SessionListCard = () => {
    return (
        <Card className={'@md:col-span-2 min-h-[24rem]'}>
            <CardHeader>
                <CardTitle>Active Sessions</CardTitle>
                <CardDescription>Manage your active sessions</CardDescription>
            </CardHeader>
        </Card>
    )
}

export default SessionListCard
