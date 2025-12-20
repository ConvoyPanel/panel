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
                const selectedItem = items.find(
                    item => item.value === field.value
                )

                return (
                    <FormItem>
                        <FormLabel>{label}</FormLabel>
                        <Select
                            onValueChange={field.onChange}
                            value={field.value}
                            disabled={disabled || formState.isSubmitting}
                        >
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder={placeholder}>
                                        {selectedItem?.label}
                                    </SelectValue>
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
