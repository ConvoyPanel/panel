import ChainSection from '@/features/servers/firewall/components/ChainSection.tsx'
import {
    type FirewallPolicy,
    type FirewallRule,
    type RuleDirection,
    appendPosition,
    deleteRule,
    firewallQueries,
    isLogging,
    moveRule,
    ruleToFormValues,
    rulesInChain,
    updateRule,
    useFirewallOptions,
    useFirewallRules,
    willBlockSsh,
    withLogging,
    withPolicy,
} from '@/features/servers/firewall/api.ts'
import useUpdateFirewallOptions from '@/features/servers/firewall/use-update-options.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Card } from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

import RuleFormDialog from './RuleFormDialog.tsx'

interface Props {
    uuid: string
}

/**
 * The whole ruleset as one ledger: the inbound chain, the outbound chain, and
 * the policy that terminates each of them.
 *
 * There is no CardHeader on purpose. The page heading already names this, and
 * a third "Firewall" between the nav item and the H1 said nothing -- the space
 * goes to the two chain headings, which do carry information.
 */
const FirewallLedger = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: rules, isLoading } = useFirewallRules(uuid)
    const { data: options } = useFirewallOptions(uuid)

    const [dialogOpen, setDialogOpen] = useState(false)
    const [editing, setEditing] = useState<FirewallRule | undefined>()
    const [creatingIn, setCreatingIn] = useState<RuleDirection>('in')

    const rulesKey = firewallQueries.rules(uuid).queryKey

    /*
     * Every write renumbers positions, so the ordering is never patched into
     * the cache -- the list is refetched and the server's wins.
     *
     * Only the two queries carrying digests are awaited. Proxmox's digest locks
     * the whole VM firewall file, so a write sent before the previous one's new
     * digest is known gets refused; returning this from a mutation keeps it
     * pending, and the controls disabled, until that can no longer happen. Refs
     * and macros gate nothing and are cluster-level anyway, and the log is a
     * read of the node's syslog that rule writes do not change -- waiting on
     * any of them just made a one-bit switch feel broken.
     */
    const refreshConfig = () =>
        Promise.all([
            queryClient.invalidateQueries({
                queryKey: firewallQueries.options(uuid).queryKey,
            }),
            queryClient.invalidateQueries({ queryKey: rulesKey }),
        ])

    const { mutate: reorder, isPending: isReordering } = useMutation({
        mutationFn: ({
            from,
            to,
            digest,
        }: {
            from: number
            to: number
            digest: string | null
        }) => moveRule(uuid, from, to, digest),
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to reorder the rules'),
                type: 'error',
            }),
        onSettled: refreshConfig,
    })

    const { mutate: toggle, isPending: isToggling } = useMutation({
        mutationFn: ({
            rule,
            enabled,
        }: {
            rule: FirewallRule
            enabled: boolean
        }) =>
            updateRule(uuid, rule.position!, {
                ...ruleToFormValues(rule),
                enabled,
            }),
        // The switch moves on click rather than a round-trip later. Turning a
        // rule off is a full rule PUT to Proxmox, and rendering the old state
        // until that came back read as the control not working at all.
        onMutate: async ({ rule, enabled }) => {
            await queryClient.cancelQueries({ queryKey: rulesKey })

            const previous = queryClient.getQueryData<FirewallRule[]>(rulesKey)

            queryClient.setQueryData<FirewallRule[]>(rulesKey, current =>
                current?.map(item =>
                    item.position === rule.position
                        ? { ...item, isEnabled: enabled }
                        : item
                )
            )

            return { previous }
        },
        onError: (e, _variables, context) => {
            if (context?.previous) {
                queryClient.setQueryData(rulesKey, context.previous)
            }

            toast.add({
                title: getApiErrorMessage(e, 'Failed to update the rule'),
                type: 'error',
            })
        },
        onSettled: refreshConfig,
    })

    const { mutate: remove } = useMutation({
        mutationFn: (rule: FirewallRule) =>
            deleteRule(uuid, rule.position!, rule.digest),
        onSuccess: () => {
            toast.add({ title: 'Rule deleted', type: 'success' })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to delete the rule'),
                type: 'error',
            }),
        onSettled: refreshConfig,
    })

    // The policy and its logging switch save on change rather than through a
    // dirty footer: each is a single choice with an immediate, visible effect,
    // and a Save button beside one select is a step that only ever gets in the
    // way. The lockout confirmation below is the one place a change is worth
    // stopping for.
    const { mutate: saveOptions, isPending: isSavingOptions } =
        useUpdateFirewallOptions(uuid)

    const onPolicyChange = async (
        direction: RuleDirection,
        policy: FirewallPolicy
    ) => {
        if (!options) return

        if (direction === 'in' && willBlockSsh(policy, rules ?? [])) {
            const confirmed = await confirm({
                title: 'This will cut off SSH',
                description:
                    'No enabled rule allows inbound traffic on port 22, so once the default policy changes you will not be able to reach this server over SSH. The console will still work.',
                confirmText: 'Apply anyway',
                confirmButton: { variant: 'destructive' },
            })

            if (!confirmed) return
        }

        saveOptions(withPolicy(options, direction, policy))
    }

    const onLoggingChange = (direction: RuleDirection, enabled: boolean) => {
        if (!options) return

        saveOptions(withLogging(options, direction, enabled))
    }

    const onDelete = async (rule: FirewallRule) => {
        const confirmed = await confirm({
            title: 'Delete rule',
            description: rule.comment
                ? `"${rule.comment}" will stop being enforced immediately.`
                : 'This rule will stop being enforced immediately.',
            confirmText: 'Delete',
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) remove(rule)
    }

    const openCreate = (direction: RuleDirection) => {
        setEditing(undefined)
        setCreatingIn(direction)
        setDialogOpen(true)
    }

    const openEdit = (rule: FirewallRule) => {
        setEditing(rule)
        setDialogOpen(true)
    }

    if (isLoading) {
        return <Skeleton className={'h-64 w-full rounded-xl'} />
    }

    const chainProps = (direction: RuleDirection) => ({
        direction,
        rules: rulesInChain(rules, direction),
        policy:
            (direction === 'in'
                ? options?.inboundPolicy
                : options?.outboundPolicy) ?? 'ACCEPT',
        isLoggingUnmatched: isLogging(
            (direction === 'in'
                ? options?.inboundLogLevel
                : options?.outboundLogLevel) ?? 'nolog'
        ),
        isPending: !options,
        isSaving: isSavingOptions,
        isMutating: isReordering || isToggling,
        onAdd: () => openCreate(direction),
        onEdit: openEdit,
        onDelete,
        onToggle: (rule: FirewallRule, enabled: boolean) =>
            toggle({ rule, enabled }),
        onReorder: (from: number, to: number, digest: string | null) =>
            reorder({ from, to, digest }),
        onPolicyChange: (policy: FirewallPolicy) =>
            onPolicyChange(direction, policy),
        onLoggingChange: (enabled: boolean) =>
            onLoggingChange(direction, enabled),
    })

    return (
        <>
            {/* divide-y draws the seam between the two chains, so the dialog
                stays outside the card rather than counting as a third one. */}
            <Card className={'divide-y'}>
                <ChainSection {...chainProps('in')} />
                <ChainSection {...chainProps('out')} />
            </Card>

            <RuleFormDialog
                uuid={uuid}
                rule={editing}
                defaultDirection={creatingIn}
                position={
                    editing ? undefined : appendPosition(rules, creatingIn)
                }
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </>
    )
}

export default FirewallLedger
