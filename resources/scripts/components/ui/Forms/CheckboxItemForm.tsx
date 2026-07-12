import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

import { Checkbox, CheckboxProps } from '@/components/ui/Checkbox'
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/Form'

interface Props extends CheckboxProps {
    name: string
    label: string
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const CheckboxItemForm = ({ name, label, formItemProps, ...props }: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem
                    {...formItemProps}
                    className={cn(
                        'flex-row items-center gap-3',
                        formItemProps?.className
                    )}
                >
                    <FormControl>
                        <Checkbox
                            {...props}
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            disabled={formState.isSubmitting}
                        />
                    </FormControl>

                    <FormLabel className='text-sm font-normal'>
                        {label}
                    </FormLabel>
                </FormItem>
            )}
        />
    )
}

export default CheckboxItemForm
