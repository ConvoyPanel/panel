import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/nodes/$nodeId/network')({
    staticData: {
        title: 'Network',
    },
})
