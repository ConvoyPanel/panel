import {
    CUSTOM_MACRO,
    type FirewallRule,
    type RuleDirection,
    type RuleFormValues,
    createRule,
    datacenterRefs,
    describeRuleIntent,
    directionLabels,
    firewallQueries,
    ruleFormDefaults,
    ruleFormSchema,
    ruleToFormValues,
    updateRule,
    useFirewallMacros,
    useFirewallRefs,
} from '@/features/servers/firewall/api.ts'
import SourceField from '@/features/servers/firewall/components/SourceField.tsx'
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
import { FieldGroup } from '@/components/ui/Field'
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
import { Separator } from '@/components/ui/Separator'
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
    /**
     * Which chain a new rule starts in, so "Add rule" opens in the chain it was
     * pressed from. Still editable under Advanced. Ignored when editing.
     */
    defaultDirection?: RuleDirection
    /** Where a new rule lands. Ignored when editing. */
    position?: number
    open: boolean
    onOpenChange: (open: boolean) => void
    trigger?: ReactElement
}

const RuleFormDialog = ({
    uuid,
    rule,
    defaultDirection = 'in',
    position,
    open,
    onOpenChange,
    trigger,
}: Props) => {
    const queryClient = useQueryClient()
    const isEditing = rule !== undefined

    const { data: macros } = useFirewallMacros(uuid)
    const { data: refs } = useFirewallRefs(uuid)

    const initialValues = (): RuleFormValues =>
        rule
            ? ruleToFormValues(rule)
            : { ...ruleFormDefaults, direction: defaultDirection }

    const form = useForm<RuleFormValues>({
        resolver: zodResolver(ruleFormSchema),
        defaultValues: initialValues(),
    })

    // A dialog kept mounted between openings would otherwise show the previous
    // rule's values, or a stale copy of this one after someone else edited it.
    useEffect(() => {
        if (open) form.reset(initialValues())
    }, [open, rule, defaultDirection])

    const values = useWatch({ control: form.control }) as RuleFormValues

    const isCustom = values.macro === CUSTOM_MACRO
    const isIcmp = ICMP_PROTOCOLS.includes(
        (values.protocol ?? '').trim().toLowerCase()
    )
    const isOutbound = values.direction === 'out'

    const macroItems = useMemo<SelectFormItem[]>(
        () => [
            // The opening value, and a real answer rather than a placeholder:
            // a rule that constrains only its source ("block everything from
            // this address") is both valid and common.
            { value: '', label: 'Any traffic' },
            // Second, above the presets: it is the other general answer, not a
            // footnote to the list. It is still not the *initial* value, which
            // is what used to open the form in its most complex state.
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

    // Only the datacenter-scoped ones; see `datacenterRefs`.
    const groups = useMemo(() => datacenterRefs(refs), [refs])

    // FormButton derives its own pending state from the form's isSubmitting,
    // which handleSubmit already holds open for the duration of this promise.
    const { mutateAsync: submit } = useMutation({
        mutationFn: (submitted: RuleFormValues) =>
            isEditing
                ? updateRule(uuid, rule.position!, submitted)
                : createRule(uuid, submitted, position),
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

    const onSubmit = async (submitted: RuleFormValues) => {
        try {
            await submit(submitted)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: getApiErrorMessage(
                    e,
                    isEditing
                        ? 'Failed to update the rule'
                        : 'Failed to add the rule'
                ),
                type: 'error',
            })
        }
    }

    const chain = directionLabels[values.direction ?? 'in'].toLowerCase()

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            {trigger && <ResponsiveDialogTrigger render={trigger} />}
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {isEditing ? `Edit ${chain} rule` : `New ${chain} rule`}
                    </ResponsiveDialogTitle>
                    {/* The description is the preview. It used to be a static
                        sentence that read the same however the form was filled
                        in; saying the rule back is the one thing the form could
                        not do, and it needs no furniture of its own to do it. */}
                    <ResponsiveDialogDescription>
                        {describeRuleIntent(values)}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)}>
                        {/* Capped at every width, not just `max-sm:`.
                            DialogContent is `fixed -translate-y-1/2`, so an
                            uncapped body grew the popup past the viewport and
                            centred it off-screen -- title above the top edge,
                            submit button below the bottom one. */}
                        <ResponsiveDialogBody>
                            {/* The scroll lives on this wrapper, not on the
                                body, so the body keeps its own responsive
                                padding.

                                `-m-1 p-1` is not spacing -- it nets to zero.
                                Scrolling one axis forces the other to clip
                                (CSS has no overflow-y:auto/overflow-x:visible),
                                so a focused field's 3px ring was being sliced
                                off at the left and right edges of the scroll
                                box. The inset gives the ring somewhere to go
                                while the fields stay aligned with the header
                                and footer. */}
                            <div
                                className={
                                    '-m-1 max-h-[60vh] overflow-y-auto p-1'
                                }
                            >
                                <FieldGroup>
                                    <TabForm
                                        name={'action'}
                                        label={'Action'}
                                        // Shown only for the option it explains.
                                        // Permanently on screen it was the second
                                        // thing read, for a choice made once.
                                        description={
                                            values.action === 'REJECT'
                                                ? 'Sends a refusal back, so the sender fails immediately. Drop stays silent and lets it time out.'
                                                : undefined
                                        }
                                        tabsListProps={{
                                            className:
                                                'grid w-full grid-cols-3',
                                        }}
                                    >
                                        {/* Named as Proxmox names them, and as the
                                        rules table renders them. */}
                                        <TabsTrigger value={'ACCEPT'}>
                                            Accept
                                        </TabsTrigger>
                                        <TabsTrigger value={'DROP'}>
                                            Drop
                                        </TabsTrigger>
                                        <TabsTrigger value={'REJECT'}>
                                            Reject
                                        </TabsTrigger>
                                    </TabForm>

                                    <SelectForm
                                        name={'macro'}
                                        label={'Service'}
                                        items={macroItems}
                                        description={
                                            'A preset fills in the protocol and ports. Choose Custom to set them yourself.'
                                        }
                                    />

                                    {/* A macro already carries these, and the API
                                    rejects a rule that sets both. */}
                                    {isCustom && (
                                        <div
                                            className={'grid grid-cols-2 gap-3'}
                                        >
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

                                    {/* The far end of the connection, which is the
                                    one the chain does not already state. */}
                                    {isOutbound ? (
                                        <SourceField
                                            name={'destinationAddress'}
                                            label={'Destination'}
                                            groups={groups}
                                        />
                                    ) : (
                                        <SourceField
                                            name={'sourceAddress'}
                                            label={'Source'}
                                            groups={groups}
                                        />
                                    )}

                                    <InputForm
                                        name={'comment'}
                                        label={'Comment'}
                                        placeholder={'What is this rule for?'}
                                    />

                                    {isEditing && (
                                        <SwitchForm
                                            name={'enabled'}
                                            label={'Rule is on'}
                                            description={
                                                'Turn off to keep the rule without enforcing it.'
                                            }
                                        />
                                    )}

                                    <Separator />

                                    <Collapsible>
                                        <CollapsibleTrigger>
                                            Advanced
                                        </CollapsibleTrigger>
                                        {/* The panel is `overflow-hidden` so it
                                            can animate its height, which also
                                            clips the 3px focus ring off every
                                            field inside it. `-m-1 p-1` nets to
                                            zero -- the fields stay where they
                                            were -- while moving the clip edge
                                            4px out so the ring has somewhere to
                                            draw. `pt-3` rather than `pt-4`
                                            because the padding now supplies the
                                            other 4px. */}
                                        <CollapsiblePanel className={'-m-1 p-1'}>
                                            <FieldGroup className={'pt-3'}>
                                                {/* Here rather than at the top of
                                                the form: the chain the Add rule
                                                button was pressed in already
                                                answered this. */}
                                                <TabForm
                                                    name={'direction'}
                                                    label={'Direction'}
                                                    tabsListProps={{
                                                        className:
                                                            'grid w-full grid-cols-2',
                                                    }}
                                                >
                                                    <TabsTrigger value={'in'}>
                                                        Inbound
                                                    </TabsTrigger>
                                                    <TabsTrigger value={'out'}>
                                                        Outbound
                                                    </TabsTrigger>
                                                </TabForm>

                                                {isOutbound ? (
                                                    <InputForm
                                                        name={'sourceAddress'}
                                                        label={'Source'}
                                                        placeholder={'Any'}
                                                    />
                                                ) : (
                                                    <InputForm
                                                        name={
                                                            'destinationAddress'
                                                        }
                                                        label={'Destination'}
                                                        placeholder={'Any'}
                                                    />
                                                )}

                                                <InputForm
                                                    name={'sourcePort'}
                                                    label={'Source port'}
                                                    placeholder={'Any'}
                                                />
                                                {/* Proxmox rejects the rule outright
                                                if this is set on a non-ICMP
                                                protocol. */}
                                                {isIcmp && (
                                                    <InputForm
                                                        name={'icmpType'}
                                                        label={'ICMP type'}
                                                        placeholder={
                                                            'echo-request'
                                                        }
                                                    />
                                                )}
                                                <InputForm
                                                    name={'interface'}
                                                    label={'Network interface'}
                                                    placeholder={
                                                        'All interfaces'
                                                    }
                                                    description={
                                                        'Restrict to one device, e.g. net0.'
                                                    }
                                                />
                                                <SelectForm
                                                    name={'logLevel'}
                                                    label={'Logging'}
                                                    items={logLevelItems}
                                                />
                                            </FieldGroup>
                                        </CollapsiblePanel>
                                    </Collapsible>
                                </FieldGroup>
                            </div>
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
