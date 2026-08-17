import FirewallController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/FirewallController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

export type FirewallRule = App.Data.Server.Proxmox.Firewall.FirewallRuleData
export type FirewallOptions =
    App.Data.Server.Proxmox.Firewall.FirewallOptionsData
export type FirewallRef = App.Data.Server.Proxmox.Firewall.FirewallRefData
export type FirewallMacro = App.Data.Server.Proxmox.Firewall.FirewallMacroData
export type FirewallLogEntry =
    App.Data.Server.Proxmox.Firewall.FirewallLogEntryData

export type RuleDirection = App.Enums.Server.Firewall.RuleDirection
export type RuleAction = App.Enums.Server.Firewall.RuleAction
export type FirewallPolicy = App.Enums.Server.Firewall.FirewallPolicy
export type FirewallLogLevel = App.Enums.Server.Firewall.FirewallLogLevel

export const ruleDirections = ['in', 'out'] as const
export const ruleActions = ['ACCEPT', 'DROP', 'REJECT'] as const
export const firewallPolicies = ['ACCEPT', 'DROP', 'REJECT'] as const

/**
 * UI-only sentinel for the Service picker's "Custom" option. Proxmox has no
 * such macro — picking it means the user sets protocol and port themselves,
 * and the submitted rule carries no macro at all.
 */
export const CUSTOM_MACRO = '__custom__'

// ─── queries ────────────────────────────────────────────────────────────────

export const firewallQueries = {
    all: (uuid: string) => ['servers', uuid, 'firewall'] as const,

    options: (uuid: string) =>
        queryOptions({
            queryKey: [...firewallQueries.all(uuid), 'options'] as const,
            queryFn: () => getOptions(uuid),
        }),

    rules: (uuid: string) =>
        queryOptions({
            queryKey: [...firewallQueries.all(uuid), 'rules'] as const,
            queryFn: () => getRules(uuid),
        }),

    refs: (uuid: string) =>
        queryOptions({
            queryKey: [...firewallQueries.all(uuid), 'refs'] as const,
            queryFn: () => getRefs(uuid),
            // Aliases and IP sets are defined by an operator, not by this page.
            staleTime: 5 * 60 * 1000,
        }),

    macros: (uuid: string) =>
        queryOptions({
            queryKey: [...firewallQueries.all(uuid), 'macros'] as const,
            queryFn: () => getMacros(uuid),
            // Only changes with a Proxmox upgrade; the backend caches it too.
            staleTime: 60 * 60 * 1000,
        }),

    log: (uuid: string, start: number, limit: number) =>
        queryOptions({
            queryKey: [
                ...firewallQueries.all(uuid),
                'log',
                { start, limit },
            ] as const,
            queryFn: () => getLog(uuid, start, limit),
            placeholderData: keepPreviousData,
        }),
}

export const getOptions = async (uuid: string): Promise<FirewallOptions> =>
    (
        await apiFetch<DataResponse<FirewallOptions>>(
            FirewallController.options(uuid)
        )
    ).data

export const getRules = async (uuid: string): Promise<FirewallRule[]> =>
    (
        await apiFetch<DataResponse<FirewallRule[]>>(
            FirewallController.index(uuid)
        )
    ).data

export const getRefs = async (uuid: string): Promise<FirewallRef[]> =>
    (await apiFetch<DataResponse<FirewallRef[]>>(FirewallController.refs(uuid)))
        .data

export const getMacros = async (uuid: string): Promise<FirewallMacro[]> =>
    (
        await apiFetch<DataResponse<FirewallMacro[]>>(
            FirewallController.macros(uuid)
        )
    ).data

export const getLog = async (
    uuid: string,
    start: number,
    limit: number
): Promise<FirewallLogEntry[]> =>
    (
        await apiFetch<DataResponse<FirewallLogEntry[]>>(
            FirewallController.log(uuid),
            { params: { start, limit } }
        )
    ).data

export const useFirewallOptions = (uuid: string) =>
    useQuery(firewallQueries.options(uuid))
export const useFirewallRules = (uuid: string) =>
    useQuery(firewallQueries.rules(uuid))
export const useFirewallRefs = (uuid: string) =>
    useQuery(firewallQueries.refs(uuid))
export const useFirewallMacros = (uuid: string) =>
    useQuery(firewallQueries.macros(uuid))
export const useFirewallLog = (
    uuid: string,
    start: number,
    limit: number,
    refetchInterval: number | false = false
) => useQuery({ ...firewallQueries.log(uuid, start, limit), refetchInterval })

// ─── rule form ──────────────────────────────────────────────────────────────

/**
 * Mirrors FirewallRuleRequest. Every optional field is a string rather than a
 * nullable one because that is what the inputs produce; {@see toRuleBody}
 * turns blanks back into the absent values the API expects.
 */
export const ruleFormSchema = z
    .object({
        direction: z.enum(ruleDirections),
        action: z.enum(ruleActions),
        enabled: z.boolean(),
        macro: z.string(),
        protocol: z.string(),
        sourceAddress: z.string(),
        destinationAddress: z.string(),
        sourcePort: z.string(),
        destinationPort: z.string(),
        icmpType: z.string(),
        interface: z.string(),
        logLevel: z.string(),
        comment: z.string(),
        /**
         * The digest of the ruleset this form was opened against. Sent back so
         * Proxmox refuses the write if anyone changed anything since — without
         * it, positions renumbering under you means editing a different rule.
         */
        digest: z.string().nullable(),
    })
    .refine(
        v =>
            v.macro !== CUSTOM_MACRO ||
            v.protocol.trim() !== '' ||
            v.destinationPort.trim() !== '',
        {
            // A custom rule matching nothing in particular is almost always a
            // mistake, and Proxmox accepts it silently.
            message: 'Set a protocol or a port, or choose a service above',
            path: ['destinationPort'],
        }
    )

export type RuleFormValues = z.infer<typeof ruleFormSchema>

export const ruleFormDefaults: RuleFormValues = {
    direction: 'in',
    action: 'ACCEPT',
    enabled: true,
    macro: '',
    protocol: '',
    sourceAddress: '',
    destinationAddress: '',
    sourcePort: '',
    destinationPort: '',
    icmpType: '',
    interface: '',
    logLevel: '',
    comment: '',
    digest: null,
}

/** Seeds the edit form from an existing rule. */
export const ruleToFormValues = (rule: FirewallRule): RuleFormValues => ({
    direction: rule.direction,
    action: rule.action,
    enabled: rule.isEnabled,
    /*
     * A rule with no macro but a protocol or port was written by hand, so it
     * reopens as Custom with those fields showing. Mapping every macro-less
     * rule to the empty option instead would hide the protocol and port inputs
     * and silently drop what they held on the next save.
     */
    macro:
        rule.macro ??
        (rule.protocol || rule.destinationPort ? CUSTOM_MACRO : ''),
    protocol: rule.protocol ?? '',
    sourceAddress: rule.sourceAddress ?? '',
    destinationAddress: rule.destinationAddress ?? '',
    sourcePort: rule.sourcePort ?? '',
    destinationPort: rule.destinationPort ?? '',
    icmpType: rule.icmpType ?? '',
    interface: rule.interface ?? '',
    logLevel: rule.logLevel ?? '',
    comment: rule.comment ?? '',
    digest: rule.digest,
})

const blankToNull = (value: string): string | null =>
    value.trim() === '' ? null : value.trim()

const toRuleBody = (values: RuleFormValues) => ({
    direction: values.direction,
    action: values.action,
    enabled: values.enabled,
    macro: values.macro === CUSTOM_MACRO ? null : blankToNull(values.macro),
    protocol: blankToNull(values.protocol),
    source_address: blankToNull(values.sourceAddress),
    destination_address: blankToNull(values.destinationAddress),
    source_port: blankToNull(values.sourcePort),
    destination_port: blankToNull(values.destinationPort),
    icmp_type: blankToNull(values.icmpType),
    interface: blankToNull(values.interface),
    log_level: blankToNull(values.logLevel),
    comment: blankToNull(values.comment),
    digest: values.digest,
})

// ─── mutations ──────────────────────────────────────────────────────────────

export const createRule = async (
    uuid: string,
    values: RuleFormValues,
    position?: number
): Promise<void> => {
    await apiFetch(FirewallController.store(uuid), {
        body: { ...toRuleBody(values), position: position ?? null },
    })
}

export const updateRule = async (
    uuid: string,
    position: number,
    values: RuleFormValues
): Promise<void> => {
    await apiFetch(FirewallController.update({ server: uuid, position }), {
        body: toRuleBody(values),
    })
}

/**
 * Positions renumber on every insert, delete, and move, so the caller must
 * refetch afterwards rather than trusting its local ordering.
 */
export const moveRule = async (
    uuid: string,
    position: number,
    newPosition: number,
    digest: string | null
): Promise<void> => {
    await apiFetch(FirewallController.move({ server: uuid, position }), {
        body: { position: newPosition, digest },
    })
}

export const deleteRule = async (
    uuid: string,
    position: number,
    digest: string | null
): Promise<void> => {
    await apiFetch(FirewallController.destroy({ server: uuid, position }), {
        body: { digest },
    })
}

export const optionsFormSchema = z.object({
    inboundPolicy: z.enum(firewallPolicies),
    outboundPolicy: z.enum(firewallPolicies),
    inboundLogLevel: z.string(),
    outboundLogLevel: z.string(),
    /**
     * The digest that came back with the options being edited. Proxmox rejects
     * the write if anything changed since, which is what stops two operators on
     * the same server from silently overwriting each other.
     */
    digest: z.string().nullable(),
})

export type OptionsFormValues = z.infer<typeof optionsFormSchema>

/**
 * The options as the form holds them, so a caller can change one field and send
 * the rest back untouched. Every write carries all four, and the digest that
 * came with them.
 */
export const optionsToFormValues = (
    options: FirewallOptions
): OptionsFormValues => ({
    inboundPolicy: options.inboundPolicy,
    outboundPolicy: options.outboundPolicy,
    inboundLogLevel: options.inboundLogLevel,
    outboundLogLevel: options.outboundLogLevel,
    digest: options.digest,
})

/**
 * The severity a direction logs at once logging is switched on. Proxmox has
 * nine; picking between them is not a decision this page asks anyone to make,
 * and one an operator has already made from Proxmox is preserved rather than
 * flattened back to this.
 */
export const DEFAULT_LOG_LEVEL: FirewallLogLevel = 'info'

export const isLogging = (level: FirewallLogLevel): boolean => level !== 'nolog'

/** The options with one direction's default policy changed. */
export const withPolicy = (
    options: FirewallOptions,
    direction: RuleDirection,
    policy: FirewallPolicy
): OptionsFormValues => {
    const values = optionsToFormValues(options)

    return direction === 'in'
        ? { ...values, inboundPolicy: policy }
        : { ...values, outboundPolicy: policy }
}

/**
 * The options with one direction's logging switched on or off.
 *
 * A severity an operator set from Proxmox survives being switched off and on
 * again in the same visit, because the options this is derived from are the
 * ones last loaded; only a direction that has never logged falls back to the
 * default.
 */
export const withLogging = (
    options: FirewallOptions,
    direction: RuleDirection,
    enabled: boolean
): OptionsFormValues => {
    const values = optionsToFormValues(options)
    const current =
        direction === 'in' ? options.inboundLogLevel : options.outboundLogLevel
    const level: FirewallLogLevel = enabled
        ? isLogging(current)
            ? current
            : DEFAULT_LOG_LEVEL
        : 'nolog'

    return direction === 'in'
        ? { ...values, inboundLogLevel: level }
        : { ...values, outboundLogLevel: level }
}

export const updateOptions = async (
    uuid: string,
    values: OptionsFormValues
): Promise<FirewallOptions> =>
    (
        await apiFetch<DataResponse<FirewallOptions>>(
            FirewallController.updateOptions(uuid),
            {
                body: {
                    inbound_policy: values.inboundPolicy,
                    outbound_policy: values.outboundPolicy,
                    inbound_log_level: values.inboundLogLevel,
                    outbound_log_level: values.outboundLogLevel,
                    digest: values.digest,
                },
            }
        )
    ).data

// ─── chains ─────────────────────────────────────────────────────────────────

/*
 * Proxmox keeps one ordered list with the directions mixed together, and a
 * rule's position is its index in that combined list. Only the order *within*
 * a direction decides anything though: the inbound chain and the outbound
 * chain are matched separately, so an inbound rule sitting "above" an outbound
 * one means nothing at all. Everything below splits the list for display while
 * keeping each rule's real, global position, which is still its identity in
 * every write.
 */

export const directionLabels: Record<RuleDirection, string> = {
    in: 'Inbound',
    out: 'Outbound',
}

export const rulesInChain = (
    rules: FirewallRule[] | undefined,
    direction: RuleDirection
): FirewallRule[] => (rules ?? []).filter(rule => rule.direction === direction)

/**
 * The ruleset with one rule moved, keeping every rule's `position` as it was.
 *
 * Deliberately not renumbered. Proxmox will renumber -- a rule's position *is*
 * its index in the combined list -- but position is also the only thing
 * identifying a rule to React and to dnd-kit, and renumbering hands every slot
 * the same key it had before. Rows then keep their DOM nodes and swap their
 * text instead of moving, which is the drop animating rather than landing.
 *
 * The stale positions live only until the write settles and the refetch brings
 * the server's numbering back; dragging is disabled for that window, so nothing
 * can address one of them in the meantime.
 */
export const reorderRules = (
    rules: FirewallRule[],
    from: number,
    to: number
): FirewallRule[] => {
    const fromIndex = rules.findIndex(rule => rule.position === from)
    const toIndex = rules.findIndex(rule => rule.position === to)

    if (fromIndex === -1 || toIndex === -1) return rules

    const next = [...rules]
    const [moved] = next.splice(fromIndex, 1)
    next.splice(toIndex, 0, moved)

    return next
}

/**
 * Where a new rule in this chain should land: after the chain's current last
 * rule, not at the end of the combined list. Appending globally would drop an
 * inbound rule below the outbound ones, which changes nothing about
 * enforcement but reads as though it did.
 */
export const appendPosition = (
    rules: FirewallRule[] | undefined,
    direction: RuleDirection
): number => {
    const all = rules ?? []
    const chain = rulesInChain(all, direction)
    const last = chain[chain.length - 1]

    return last?.position != null ? last.position + 1 : all.length
}

// ─── display helpers ────────────────────────────────────────────────────────

const plural = (count: number, noun: string): string =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

export const policyAdjectives: Record<FirewallPolicy, string> = {
    ACCEPT: 'Accepted',
    DROP: 'Dropped',
    REJECT: 'Rejected',
}

/** The count line beside a chain's name: `4 rules · 1 off`. */
export const describeChain = (rules: FirewallRule[]): string => {
    if (rules.length === 0) return 'No rules'

    const off = rules.filter(rule => !rule.isEnabled).length

    return off === 0
        ? plural(rules.length, 'rule')
        : `${plural(rules.length, 'rule')} · ${off} off`
}

/**
 * The lead-in to a chain's policy row. With no rules above it there is nothing
 * for traffic to be "unmatched" by, so the sentence covers all of it.
 */
export const describePolicyRow = (
    direction: RuleDirection,
    hasRules: boolean
): string =>
    hasRules
        ? `Unmatched ${directionLabels[direction].toLowerCase()} traffic is`
        : `All ${directionLabels[direction].toLowerCase()} traffic is`

/**
 * The aliases and IP sets worth offering in a rule.
 *
 * Only the datacenter-scoped ones. The guest-scoped entries are Convoy's own:
 * `configureFirewall()` turns on Proxmox's `ipfilter`, which keeps an
 * `ipfilter-net0` set per interface holding the addresses that interface may
 * send from, and `clearIpsets()` rebuilds them on every address change. As a
 * rule's source that reads "traffic claiming to come from me", which is not a
 * rule anyone writes -- and on a server with no datacenter groups it was the
 * only suggestion the field ever made.
 */
export const datacenterRefs = (
    refs: FirewallRef[] | undefined
): FirewallRef[] => (refs ?? []).filter(ref => ref.scope === 'dc')

const trimmed = (value: string): string => value.trim()

/** What a half-filled form currently matches, for {@see describeRuleIntent}. */
const describeFormTraffic = (values: RuleFormValues): string => {
    if (values.macro !== CUSTOM_MACRO && trimmed(values.macro) !== '') {
        return trimmed(values.macro)
    }

    const protocol = trimmed(values.protocol).toLowerCase()
    const port = trimmed(values.destinationPort)

    if (protocol && port) return `${protocol}/${port}`
    if (protocol) return protocol
    if (port) return `port ${port}`

    return 'all traffic'
}

/**
 * The rule as a sentence, for the dialog's description line.
 *
 * This replaces a static "Allow or block specific traffic to this server",
 * which said the same thing however the form was filled in. Reading back what
 * is about to be written is the one thing the form could never do.
 */
export const describeRuleIntent = (values: RuleFormValues): string => {
    const verbs: Record<RuleAction, string> = {
        ACCEPT: 'Accepts',
        DROP: 'Drops',
        REJECT: 'Rejects',
    }

    const isInbound = values.direction === 'in'
    const peer = trimmed(
        isInbound ? values.sourceAddress : values.destinationAddress
    )

    return `${verbs[values.action]} ${describeFormTraffic(values)} ${
        isInbound ? 'from' : 'to'
    } ${peer === '' ? 'anywhere' : peer}.`
}

/**
 * What a rule matches, in one string: `tcp/22`, `icmp`, or the macro's name.
 * Returns null when the rule constrains neither, which reads as "any traffic".
 */
export const describeRuleTraffic = (rule: FirewallRule): string | null => {
    if (rule.macro) return rule.macro

    const protocol = rule.protocol?.toLowerCase()

    if (protocol && rule.destinationPort)
        return `${protocol}/${rule.destinationPort}`
    if (protocol) return protocol
    if (rule.destinationPort) return `port ${rule.destinationPort}`

    return null
}

/**
 * True when the inbound policy would drop SSH and no enabled rule lets it back
 * in — i.e. saving is about to lock the user out of their own server.
 */
export const willBlockSsh = (
    policy: FirewallPolicy,
    rules: FirewallRule[]
): boolean => {
    if (policy === 'ACCEPT') return false

    return !rules.some(
        rule =>
            rule.isEnabled &&
            rule.direction === 'in' &&
            rule.action === 'ACCEPT' &&
            (rule.macro?.toLowerCase() === 'ssh' ||
                matchesPort(rule.destinationPort, 22))
    )
}

/** Whether a PVE port spec (`22`, `20:25`, `80,443`) covers a given port. */
const matchesPort = (spec: string | null, port: number): boolean => {
    if (!spec) return false

    return spec.split(',').some(part => {
        const [start, end] = part.trim().split(':')
        const from = Number(start)

        if (Number.isNaN(from)) return false
        if (end === undefined) return from === port

        const to = Number(end)

        return !Number.isNaN(to) && port >= from && port <= to
    })
}
