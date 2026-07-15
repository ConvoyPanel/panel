import { ReactNode } from 'react'

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

export interface SelectFormItem {
    value: string
    label: ReactNode
}

interface Props {
    name: string
    label: string
    description?: ReactNode
    placeholder?: string
    items: SelectFormItem[]
    disabled?: boolean
}

const SelectForm = ({
    name,
    label,
    description,
    placeholder,
    items,
    disabled,
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => {
                return (
                    <FormItem>
                        <FormLabel>{label}</FormLabel>
                        <Select
                            items={items}
                            modal={false}
                            onValueChange={field.onChange}
                            // `?? null` is load-bearing: Base UI reads an
                            // `undefined` value as "uncontrolled" and then
                            // ignores every later value. Forms that mount before
                            // a `reset()` lands (e.g. while a query resolves)
                            // would otherwise stay stuck on the placeholder.
                            value={field.value ?? null}
                            disabled={disabled || formState.isSubmitting}
                        >
                            <FormControl>
                                <SelectTrigger className='w-full'>
                                    <SelectValue placeholder={placeholder} />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                {items.map(item => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {description && (
                            <FormDescription>{description}</FormDescription>
                        )}
                        <FormMessage />
                    </FormItem>
                )
            }}
        />
    )
}

export default SelectForm
