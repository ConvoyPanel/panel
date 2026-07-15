import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/nodes/$nodeId/ipam')({
    staticData: {
        title: 'IPAM',
    },
})
