import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/_dashboard/security')({
  component: () => <div>Hello /_app/_dashboard/security!</div>
})