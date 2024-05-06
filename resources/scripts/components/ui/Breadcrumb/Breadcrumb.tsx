import { ComponentPropsWithoutRef, ReactNode, forwardRef } from 'react'

const Breadcrumb = forwardRef<
    HTMLElement,
    ComponentPropsWithoutRef<'nav'> & {
        separator?: ReactNode
    }
>(({ ...props }, ref) => <nav ref={ref} aria-label='breadcrumb' {...props} />)
Breadcrumb.displayName = 'Breadcrumb'

export default Breadcrumb
