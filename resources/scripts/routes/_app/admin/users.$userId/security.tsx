import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/users/$userId/security')({
    staticData: {
        title: 'Security',
    },
})
