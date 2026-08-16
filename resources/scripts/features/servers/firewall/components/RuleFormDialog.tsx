import {
    CUSTOM_MACRO,
    type FirewallRule,
    type RuleFormValues,
    createRule,
    firewallQueries,
    ruleFormDefaults,
    ruleFormSchema,
    ruleToFormValues,
    updateRule,
    useFirewallMacros,
    useFirewallRefs,
} from '@/features/servers/firewall/api.ts'
import { getApiErrorMessage, handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { type ReactElement, useEffect, useMemo } from 'react'
import { useForm, useWatch } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'
import { Form, FormButton } from '@/components/ui/Form'
import {
    InputForm,
    SelectForm,
    type SelectFormItem,
    SwitchForm,
} from '@/components/ui/Forms'
import TabForm from '@/components/ui/Forms/TabForm.tsx'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { TabsTrigger } from '@/components/ui/Tabs'
import { toast } from '@/components/ui/Toast'

const logLevelItems: SelectFormItem[] = [
    { value: '', label: "Don't log" },
    { value: 'info', label: 'Log matches' },
]

// Only the levels a rule realistically needs. The nine syslog severities are a
// Proxmox implementation detail; a rule either records its matches or it does
// not, and an operator who wants `debug` has the Proxmox UI.
const ICMP_PROTOCOLS = ['icmp', 'icmpv6', 'ipv6-icmp']

interface Props {
    uuid: string
    /** The rule being edited. Absent means this dialog creates one. */
    rule?: FirewallRule
    /** Where a new rule lands. Ignored when editing. */
    position?: number
    open: boolean
    onOpenChange: (open: boolean) => void
    trigger?: ReactElement
}

const RuleFormDialog = ({
    uuid,
    rule,
    position,
    open,
    onOpenChange,
    trigger,
}: Props) => {
    const queryClient = useQueryClient()
    const isEditing = rule !== undefined

    const { data: macros } = useFirewallMacros(uuid)
    const { data: refs } = useFirewallRefs(uuid)

    const form = useForm<RuleFormValues>({
        resolver: zodResolver(ruleFormSchema),
        defaultValues: rule ? ruleToFormValues(rule) : ruleFormDefaults,
    })

    // A dialog kept mounted between openings would otherwise show the previous
    // rule's values, or a stale copy of this one after someone else edited it.
    useEffect(() => {
        if (open) form.reset(rule ? ruleToFormValues(rule) : ruleFormDefaults)
    }, [open, rule])

    const macro = useWatch({ control: form.control, name: 'macro' })
    const protocol = useWatch({ control: form.control, name: 'protocol' })

    const isCustom = macro === CUSTOM_MACRO
    const isIcmp = ICMP_PROTOCOLS.includes(protocol.trim().toLowerCase())

    const macroItems = useMemo<SelectFormItem[]>(
        () => [
            { value: CUSTOM_MACRO, label: 'Custom — set protocol and port' },
            ...(macros ?? []).map(item => ({
                value: item.name,
                label: item.description
                    ? `${item.name} — ${item.description}`
                    : item.name,
            })),
        ],
        [macros]
    )

    // Aliases and IP sets an operator defined, cluster-wide or on this server.
    // Shown as a hint rather than a picker: the field also takes raw CIDRs and
    // ranges, and a combobox that refuses those would be a downgrade.
    const refHint = useMemo(() => {
        if (!refs?.length) return undefined

        return `Saved groups: ${refs.map(ref => ref.reference).join(', ')}`
    }, [refs])

    // FormButton derives its own pending state from the form's isSubmitting,
    // which handleSubmit already holds open for the duration of this promise.
    const { mutateAsync: submit } = useMutation({
        mutationFn: (values: RuleFormValues) =>
            isEditing
                ? updateRule(uuid, rule.position!, values)
                : createRule(uuid, values, position),
        onSuccess: () => {
            // Positions renumber on any write, so the whole list is refetched
            // rather than patched.
            queryClient.invalidateQueries({
                queryKey: firewallQueries.all(uuid),
            })
            toast.add({
                title: isEditing ? 'Rule updated' : 'Rule added',
                type: 'success',
            })
            onOpenChange(false)
        },
    })

    const onSubmit = async (values: RuleFormValues) => {
        try {
            await submit(values)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: getApiErrorMessage(
                    e,
                    isEditing ? 'Failed to update the rule' : 'Failed to add the rule'
                ),
                type: 'error',
            })
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            {trigger && <ResponsiveDialogTrigger render={trigger} />}
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {isEditing ? 'Edit rule' : 'Add rule'}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Allow or block specific traffic to this server.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)}>
                        <ResponsiveDialogBody
                            className={
                                'space-y-4 max-sm:max-h-[60vh] max-sm:overflow-y-auto'
                            }
                        >
                            <TabForm
                                name={'direction'}
                                label={'Direction'}
                                tabsListProps={{
                                    className: 'grid w-full grid-cols-2',
                                }}
                            >
                                <TabsTrigger value={'in'}>Incoming</TabsTrigger>
                                <TabsTrigger value={'out'}>Outgoing</TabsTrigger>
                            </TabForm>

                            <TabForm
                                name={'action'}
                                label={'Action'}
                                description={
                                    'Block discards the traffic silently, so the sender times out. Reject sends a refusal back, so it fails immediately.'
                                }
                                tabsListProps={{
                                    className: 'grid w-full grid-cols-3',
                                }}
                            >
                                <TabsTrigger value={'ACCEPT'}>Allow</TabsTrigger>
                                <TabsTrigger value={'DROP'}>Block</TabsTrigger>
                                <TabsTrigger value={'REJECT'}>
                                    Reject
                                </TabsTrigger>
                            </TabForm>

                            <SelectForm
                                name={'macro'}
                                label={'Service'}
                                items={macroItems}
                                description={
                                    'A preset fills in the protocol and ports for you.'
                                }
                            />

                            {/* A macro already carries these, and the API
                                rejects a rule that sets both. */}
                            {isCustom && (
                                <div className={'grid grid-cols-2 gap-3'}>
                                    <InputForm
                                        name={'protocol'}
                                        label={'Protocol'}
                                        placeholder={'tcp'}
                                    />
                                    <InputForm
                                        name={'destinationPort'}
                                        label={'Port'}
                                        placeholder={'443 or 80:85'}
                                    />
                                </div>
                            )}

                            <InputForm
                                name={'sourceAddress'}
                                label={'Source'}
                                placeholder={'Any'}
                                description={
                                    refHint ??
                                    'An address, or a range like 10.0.0.0/8. Leave blank for any.'
                                }
                            />

                            <InputForm
                                name={'comment'}
                                label={'Comment'}
                                placeholder={'What is this rule for?'}
                            />

                            <Collapsible>
                                <CollapsibleTrigger>Advanced</CollapsibleTrigger>
                                <CollapsiblePanel>
                                    <div className={'space-y-4 pt-3'}>
                                        <InputForm
                                            name={'destinationAddress'}
                                            label={'Destination'}
                                            placeholder={'Any'}
                                        />
                                        <InputForm
                                            name={'sourcePort'}
                                            label={'Source port'}
                                            placeholder={'Any'}
                                        />
                                        {/* Proxmox rejects the rule outright if
                                            this is set on a non-ICMP protocol. */}
                                        {isIcmp && (
                                            <InputForm
                                                name={'icmpType'}
                                                label={'ICMP type'}
                                                placeholder={'echo-request'}
                                            />
                                        )}
                                        <InputForm
                                            name={'interface'}
                                            label={'Network interface'}
                                            placeholder={'All interfaces'}
                                            description={
                                                'Restrict to one device, e.g. net0.'
                                            }
                                        />
                                        <SelectForm
                                            name={'logLevel'}
                                            label={'Logging'}
                                            items={logLevelItems}
                                        />
                                        <SwitchForm
                                            name={'enabled'}
                                            label={'Rule is on'}
                                            description={
                                                'Turn off to keep the rule without enforcing it.'
                                            }
                                        />
                                    </div>
                                </CollapsiblePanel>
                            </Collapsible>
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>
                                {isEditing ? 'Save rule' : 'Add rule'}
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RuleFormDialog
