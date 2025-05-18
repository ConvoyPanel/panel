import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/')({
    component: AdminDashboard,
})

function AdminDashboard() {
    return (
        <>
            <Heading>Admin Dashboard</Heading>
            <div className='grid grid-cols-2 gap-2 @md:gap-4 @lg:grid-cols-4'></div>
        </>
    )
}
