import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/nodes/$nodeId/')({
  component: RouteComponent,
})

function RouteComponent() {
  return <div>Hello "/_app/admin/nodes/$nodeId/"!</div>
}
