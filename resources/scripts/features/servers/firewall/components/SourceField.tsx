import type { FirewallRef } from '@/features/servers/firewall/api.ts'
import { IconChevronDown } from '@tabler/icons-react'

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { Input } from '@/components/ui/Input'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/InputGroup'

interface Props {
    /** `sourceAddress` on an inbound rule, `destinationAddress` on an outbound one. */
    name: string
    label: string
    /** Datacenter-scoped aliases and IP sets, already filtered. */
    groups: FirewallRef[]
}

/**
 * An address field that can also name a group the host defined.
 *
 * The picker only exists when there is something to pick: with no datacenter
 * groups -- the usual case -- this is an ordinary input, because a control
 * offering nothing is worse than no control. The field stays typeable either
 * way, since it also takes plain addresses, CIDRs and ranges.
 */
const SourceField = ({ name, label, groups }: Props) => (
    <FormField
        name={name}
        render={({ field, formState }) => (
            <FormItem>
                <FormLabel>{label}</FormLabel>
                <FormControl>
                    {groups.length === 0 ? (
                        <Input
                            {...field}
                            placeholder={'Anywhere'}
                            disabled={formState.isSubmitting}
                        />
                    ) : (
                        <InputGroup>
                            <InputGroupInput
                                {...field}
                                placeholder={'Anywhere'}
                                disabled={formState.isSubmitting}
                            />
                            <InputGroupAddon align={'inline-end'}>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <InputGroupButton>
                                            Groups
                                            <IconChevronDown />
                                        </InputGroupButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align={'end'}
                                        className={'max-w-72'}
                                    >
                                        <DropdownMenuLabel>
                                            Datacenter groups
                                        </DropdownMenuLabel>
                                        {groups.map(group => (
                                            <DropdownMenuItem
                                                key={group.reference}
                                                onClick={() =>
                                                    field.onChange(
                                                        group.reference
                                                    )
                                                }
                                                className={'flex-col items-start gap-0.5'}
                                            >
                                                <span className={'font-mono text-xs'}>
                                                    {group.reference}
                                                </span>
                                                {/* The operator's own note on
                                                    what the group contains --
                                                    without it the name alone
                                                    asks you to guess. */}
                                                {group.comment && (
                                                    <span
                                                        className={
                                                            'text-xs text-muted-foreground'
                                                        }
                                                    >
                                                        {group.comment}
                                                    </span>
                                                )}
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </InputGroupAddon>
                        </InputGroup>
                    )}
                </FormControl>
                <FormDescription>
                    {groups.length === 0
                        ? 'An address or range, like 10.0.0.0/8. Leave blank for any.'
                        : 'An address or range, or a group your host has defined.'}
                </FormDescription>
                <FormMessage />
            </FormItem>
        )}
    />
)

export default SourceField
