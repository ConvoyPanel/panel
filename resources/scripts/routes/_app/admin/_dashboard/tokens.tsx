import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/tokens')({
    staticData: {
        title: 'API Tokens',
    },
})
