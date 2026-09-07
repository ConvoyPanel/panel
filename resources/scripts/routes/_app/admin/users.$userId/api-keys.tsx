import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/users/$userId/api-keys')({
    staticData: {
        title: 'API keys',
    },
})
