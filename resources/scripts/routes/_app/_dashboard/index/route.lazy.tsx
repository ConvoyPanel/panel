import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/_dashboard/')({
    component: Dashboard,
})

function Dashboard() {
    return <div>test</div>
}
