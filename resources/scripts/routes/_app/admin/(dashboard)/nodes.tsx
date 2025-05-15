import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/(dashboard)/nodes')({
  staticData: {
    title: 'Nodes',
  },
})
