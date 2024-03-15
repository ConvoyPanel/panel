import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/')({
    component: ClientDashboard,
})

function ClientDashboard() {
    return <h3>authenticated!</h3>
}
