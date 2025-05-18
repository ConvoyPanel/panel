import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import useAddressBlockSWR, {
  preloadAddressBlock,
} from '@/api/admin/addressBlockGroups/addressBlocks/use-address-block-swr.ts'

export const Route = createFileRoute(
  '/_app/admin/_dashboard/ipam/$addressBlockGroupId/blocks/$addressBlockId',
)({
  loader: ({ params: { addressBlockGroupId, addressBlockId } }) =>
    preloadAddressBlock(
      Number(addressBlockGroupId),
      Number(addressBlockId),
    ).catch(processAxiosError),
  component: AddressBlockLayout,
})

function AddressBlockLayout() {
  const { data: block } = useAddressBlockSWR()

  useTitle(block?.name ?? `${block?.baseIp}/${block?.prefixLengthFrom}`)

  return (
    <>
      <Outlet />
    </>
  )
}
