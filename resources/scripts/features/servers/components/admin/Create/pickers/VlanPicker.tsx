import { NetworkInterface } from '@/types/network-interface.ts'
import { useFormContext } from 'react-hook-form'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

/**
 * Base UI reads an empty-string option value as "no selection", so inheriting
 * needs a value of its own. It maps back to '' on the way out, which is what
 * the schema preprocesses to a null `vlan_tag`.
 */
const INHERIT = '__inherit__'

interface Props {
    networkInterface?: NetworkInterface
}

/**
 * A server can only be put on a VLAN that has been declared on its bridge, so
 * this is a picker over the registry rather than a free-text tag box. The
 * matching server-side rule is `VlanIsDeclaredOnInterface`.
 */
const VlanPicker = ({ networkInterface }: Props) => {
    const { control } = useFormContext()

    // Declared only. An undeclared VLAN is listed on the Network page because
    // servers are already on it, but it has no `vlans` row — offering it here
    // would hand the user a choice `VlanIsDeclaredOnInterface` then rejects.
    const vlans = (networkInterface?.vlans ?? []).filter(
        vlan => vlan.id !== null
    )
    const isTrunk = networkInterface?.isVlanAware ?? false
    const disabled = !isTrunk || vlans.length === 0

    const inheritLabel = networkInterface?.vlanTag
        ? `Inherit bridge default (VLAN ${networkInterface.vlanTag})`
        : 'No VLAN'

    // Passed to `Select` as well as rendered: Base UI resolves the trigger's
    // label from this list, and without it the raw value (the sentinel) shows.
    const items = [
        { value: INHERIT, label: inheritLabel },
        ...vlans.map(vlan => ({
            value: String(vlan.tag),
            label: (
                <span
                    className={'flex w-full items-center justify-between gap-2'}
                >
                    <span>VLAN {vlan.tag}</span>
                    <span className={'text-muted-foreground text-xs'}>
                        {vlan.name ?? 'Unnamed'}
                    </span>
                </span>
            ),
        })),
    ]

    const description = !networkInterface
        ? 'Select a network interface first.'
        : !isTrunk
          ? `${networkInterface.name} is not VLAN-aware, so servers on it are untagged.`
          : vlans.length === 0
            ? // The dead end the strict rule creates: a freshly configured trunk
              // has nothing to choose from until someone declares a VLAN.
              `No VLANs declared on ${networkInterface.name}. Declare one on the node's Network page first.`
            : 'Optional. Leave on the default to inherit the bridge.'

    return (
        <FormField
            control={control}
            name={'vlanTag'}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>VLAN</FormLabel>
                    <Select
                        items={items}
                        onValueChange={value =>
                            field.onChange(value === INHERIT ? '' : value)
                        }
                        value={
                            field.value === '' || field.value == null
                                ? INHERIT
                                : String(field.value)
                        }
                        disabled={disabled}
                    >
                        <FormControl>
                            <SelectTrigger className={'w-full'}>
                                <SelectValue placeholder={inheritLabel} />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {items.map(item => (
                                <SelectItem key={item.value} value={item.value}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <FormDescription>{description}</FormDescription>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default VlanPicker
