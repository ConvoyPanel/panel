import FirewallController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/FirewallController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

export type FirewallRule = App.Data.Server.Proxmox.Firewall.FirewallRuleData
export type FirewallOptions = App.Data.Server.Proxmox.Firewall.FirewallOptionsData
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
    (await apiFetch<DataResponse<FirewallOptions>>(FirewallController.options(uuid)))
        .data

export const getRules = async (uuid: string): Promise<FirewallRule[]> =>
    (await apiFetch<DataResponse<FirewallRule[]>>(FirewallController.index(uuid)))
        .data

export const getRefs = async (uuid: string): Promise<FirewallRef[]> =>
    (await apiFetch<DataResponse<FirewallRef[]>>(FirewallController.refs(uuid))).data

export const getMacros = async (uuid: string): Promise<FirewallMacro[]> =>
    (await apiFetch<DataResponse<FirewallMacro[]>>(FirewallController.macros(uuid)))
        .data

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
        (v) =>
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
    macro: CUSTOM_MACRO,
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
    macro: rule.macro ?? CUSTOM_MACRO,
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

// ─── display helpers ────────────────────────────────────────────────────────

/**
 * What a rule matches, in one string: `tcp/22`, `icmp`, or the macro's name.
 * Returns null when the rule constrains neither, which reads as "any traffic".
 */
export const describeRuleTraffic = (rule: FirewallRule): string | null => {
    if (rule.macro) return rule.macro

    const protocol = rule.protocol?.toLowerCase()

    if (protocol && rule.destinationPort) return `${protocol}/${rule.destinationPort}`
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
        (rule) =>
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

    return spec.split(',').some((part) => {
        const [start, end] = part.trim().split(':')
        const from = Number(start)

        if (Number.isNaN(from)) return false
        if (end === undefined) return from === port

        const to = Number(end)

        return !Number.isNaN(to) && port >= from && port <= to
    })
}
