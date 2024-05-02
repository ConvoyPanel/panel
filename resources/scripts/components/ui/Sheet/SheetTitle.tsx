import { cn } from '@/utils'
import * as SheetPrimitive from '@radix-ui/react-dialog'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

const SheetTitle = forwardRef<
    ElementRef<typeof SheetPrimitive.Title>,
    ComponentPropsWithoutRef<typeof SheetPrimitive.Title>
>(({ className, ...props }, ref) => (
    <SheetPrimitive.Title
        ref={ref}
        className={cn('text-lg font-semibold text-foreground', className)}
        {...props}
    />
))
SheetTitle.displayName = SheetPrimitive.Title.displayName

export default SheetTitle
