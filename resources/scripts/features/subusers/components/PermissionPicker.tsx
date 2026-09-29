import {
    type ServerPermission,
    permissionDescriptions,
    permissionGroups,
    permissionLabels,
} from '@/features/subusers/types.ts'
import { useFormContext } from 'react-hook-form'

import { Checkbox } from '@/components/ui/Checkbox'
import { FormDescription, FormItem, FormLabel } from '@/components/ui/Form'
import { Label } from '@/components/ui/Label'

interface Props {
    /** The form field holding the selected permission values. */
    name: string
}

/**
 * The permission checklist, grouped the way the server's own sidebar is.
 *
 * Not `CheckboxForm` per permission: that component owns one boolean field each, and this is one
 * array field with twenty boxes over it. The box itself is the same boxed card, so the picker
 * reads like the ones in the create wizards rather than like a new pattern.
 */
const PermissionPicker = ({ name }: Props) => {
    const form = useFormContext()
    const selected: ServerPermission[] = form.watch(name) ?? []

    const toggle = (permission: ServerPermission, checked: boolean) => {
        form.setValue(
            name,
            checked
                ? [...selected, permission]
                : selected.filter(value => value !== permission),
            { shouldDirty: true }
        )
    }

    return (
        <div className={'flex flex-col gap-4'}>
            {permissionGroups.map(group => (
                <div key={group.label} className={'flex flex-col gap-2'}>
                    <Label className={'text-muted-foreground text-xs'}>
                        {group.label}
                    </Label>
                    <div className={'grid gap-2 @md:grid-cols-2'}>
                        {group.permissions.map(permission => (
                            <FormItem
                                key={permission}
                                className={
                                    'flex-row items-start gap-3 rounded-lg border p-3 shadow-xs'
                                }
                            >
                                <Checkbox
                                    id={`${name}-${permission}`}
                                    checked={selected.includes(permission)}
                                    onCheckedChange={checked =>
                                        toggle(permission, checked === true)
                                    }
                                    disabled={form.formState.isSubmitting}
                                />
                                <div
                                    className={
                                        'flex flex-col gap-1 leading-none'
                                    }
                                >
                                    <FormLabel
                                        htmlFor={`${name}-${permission}`}
                                    >
                                        {permissionLabels[permission]}
                                    </FormLabel>
                                    {permissionDescriptions[permission] && (
                                        <FormDescription>
                                            {permissionDescriptions[permission]}
                                        </FormDescription>
                                    )}
                                </div>
                            </FormItem>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    )
}

export default PermissionPicker
