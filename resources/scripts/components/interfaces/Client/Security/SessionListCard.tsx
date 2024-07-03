import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'

const SessionListCard = () => {
    return (
        <Card className={'min-h-[24rem] md:col-span-2'}>
            <CardHeader>
                <CardTitle>Active Sessions</CardTitle>
                <CardDescription>Manage your active sessions</CardDescription>
            </CardHeader>
        </Card>
    )
}

export default SessionListCard
