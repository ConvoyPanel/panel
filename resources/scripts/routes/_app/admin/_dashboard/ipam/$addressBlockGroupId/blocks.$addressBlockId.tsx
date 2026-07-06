import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import useAddressBlock, {
  preloadAddressBlock,
} from '@/api/admin/addressBlockGroups/addressBlocks/use-address-block.ts'

export const Route = createFileRoute(
  '/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks/$addressBlockId',
)({
  loader: ({ params: { addressBlockGroupId, addressBlockId } }) =>
    preloadAddressBlock(
      Number(addressBlockGroupId),
      Number(addressBlockId),
    ).catch(processAxiosError),
  component: AddressBlockLayout,
    staticData: {
      title: 'IP Block',
    }
})

function AddressBlockLayout() {
  const { data: block } = useAddressBlock()

  useTitle(block?.name ?? `${block?.baseIp}/${block?.prefixLengthFrom}`)

  return (
    <>
      <Outlet />
    </>
  )
}
