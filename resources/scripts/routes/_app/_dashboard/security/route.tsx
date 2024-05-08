import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/_dashboard/security')({
    meta: () => [{ title: 'Security' }],
})
