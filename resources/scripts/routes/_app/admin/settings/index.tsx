import { createFileRoute, redirect } from '@tanstack/react-router'

// /admin/settings has no landing page of its own — send it to the first section.
export const Route = createFileRoute('/_app/admin/settings/')({
    beforeLoad: () => {
        throw redirect({ to: '/admin/settings/bandwidth' })
    },
})
