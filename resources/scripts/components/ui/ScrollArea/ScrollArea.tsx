import { cn } from '@/utils'
import * as ScrollAreaPrimitive from '@radix-ui/react-scroll-area'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import ScrollBar from './ScrollBar'

interface Props
    extends ComponentPropsWithoutRef<typeof ScrollAreaPrimitive.Root> {
    /** Props passed down to the viewport element */
    viewportProps?: ComponentPropsWithoutRef<'div'>

    /** Called with current position (`x` and `y` coordinates) when viewport is scrolled */
    onScrollPositionChange?: (position: { x: number; y: number }) => void

    /** Called when scrollarea is scrolled all the way to the bottom */
    onBottomReached?: () => void

    /** Called when scrollarea is scrolled all the way to the top */
    onTopReached?: () => void
}

const ScrollArea = forwardRef<
    ElementRef<typeof ScrollAreaPrimitive.Root>,
    Props
>(
    (
        {
            className,
            children,
            viewportProps,
            onScrollPositionChange,
            onBottomReached,
            onTopReached,
            ...props
        },
        ref
    ) => (
        <ScrollAreaPrimitive.Root
            ref={ref}
            className={cn(
                'relative overflow-hidden [&_[data-radix-scroll-area-viewport]>:first-child]:!block',
                className
            )}
            {...props}
        >
            <ScrollAreaPrimitive.Viewport
                {...viewportProps}
                onScroll={e => {
                    viewportProps?.onScroll?.(e)
                    onScrollPositionChange?.({
                        x: e.currentTarget.scrollLeft,
                        y: e.currentTarget.scrollTop,
                    })
                    const { scrollTop, scrollHeight, clientHeight } =
                        e.currentTarget
                    if (scrollTop - (scrollHeight - clientHeight) >= 0) {
                        onBottomReached?.()
                    }
                    if (scrollTop === 0) {
                        onTopReached?.()
                    }
                }}
                className={cn(
                    'h-full w-full rounded-[inherit]',
                    viewportProps?.className
                )}
            >
                {children}
            </ScrollAreaPrimitive.Viewport>
            <ScrollBar />
            <ScrollAreaPrimitive.Corner />
        </ScrollAreaPrimitive.Root>
    )
)
ScrollArea.displayName = ScrollAreaPrimitive.Root.displayName

export default ScrollArea
