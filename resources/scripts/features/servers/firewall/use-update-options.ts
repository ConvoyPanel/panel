import {
    type OptionsFormValues,
    firewallQueries,
    updateOptions,
} from '@/features/servers/firewall/api.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { useMutation, useQueryClient } from '@tanstack/react-query'

import { toast } from '@/components/ui/Toast'

/**
 * Writes the firewall options, whole. Two surfaces change them -- the policy
 * row at the foot of each chain, and the activity list's "turn on logging" --
 * and both send all four fields back with the digest they were read at, so
 * they share this rather than each rebuilding the payload.
 *
 * That digest is Proxmox's optimistic lock, and it covers the entire VM
 * firewall file rather than the field being written. So a second write sent
 * before the first one's new digest is known is refused outright -- which is
 * what "Failed to update the firewall" was, every time a switch got flipped
 * twice in quick succession. Two things stop it: the write's own response
 * carries the fresh digest and is put straight into the cache, and the
 * invalidation is returned so the mutation stays pending until the rules have
 * caught up too, keeping the controls disabled across the whole window.
 */
const useUpdateFirewallOptions = (uuid: string) => {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (values: OptionsFormValues) => updateOptions(uuid, values),
        onSuccess: fresh => {
            // The endpoint re-reads the options after writing them, so this is
            // server truth and not a guess -- no GET needed to unblock.
            queryClient.setQueryData(
                firewallQueries.options(uuid).queryKey,
                fresh
            )

            // Logging changes what the activity list will show, but no write is
            // blocked on it, so it refreshes without holding the mutation open.
            queryClient.invalidateQueries({
                queryKey: [...firewallQueries.all(uuid), 'log'],
            })

            // Options and rules share one config file on the node, so a write
            // to either moves the digest on both.
            return queryClient.invalidateQueries({
                queryKey: firewallQueries.rules(uuid).queryKey,
            })
        },
        onError: e => {
            toast.add({
                title: getApiErrorMessage(e, 'Failed to update the firewall'),
                type: 'error',
            })

            // The likeliest cause is a digest Proxmox no longer accepts, so the
            // controls have to snap back to what is actually configured rather
            // than sit showing a change that did not happen.
            return queryClient.invalidateQueries({
                queryKey: firewallQueries.all(uuid),
            })
        },
    })
}

export default useUpdateFirewallOptions
