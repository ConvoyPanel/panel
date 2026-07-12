import { cn } from '@/utils'
import { HTMLAttributes, forwardRef, useId } from 'react'

import { FormItemContext } from './form-item-provider'


const FormItem = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => {
        const id = useId()

        return (
            <FormItemContext.Provider value={{ id }}>
                <div
                    ref={ref}
                    role='group'
                    data-slot='field'
                    className={cn(
                        'group/field flex w-full flex-col gap-2',
                        className
                    )}
                    {...props}
                />
            </FormItemContext.Provider>
        )
    }
)
FormItem.displayName = 'FormItem'

export default FormItem
