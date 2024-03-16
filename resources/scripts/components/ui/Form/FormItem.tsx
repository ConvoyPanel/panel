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
                    className={cn('space-y-2', className)}
                    {...props}
                />
            </FormItemContext.Provider>
        )
    }
)
FormItem.displayName = 'FormItem'

export default FormItem
