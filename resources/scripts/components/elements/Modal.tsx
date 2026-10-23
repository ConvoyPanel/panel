import styled from '@emotion/styled'
import { Dialog } from '@headlessui/react'
import React, { ComponentProps, ReactNode } from 'react'
import tw from 'twin.macro'

import Drawer from '@/components/elements/Drawer'
import LoadingDots from '@/components/elements/LoadingDots'

interface Modal extends React.FC<ModalProps> {
    Header: React.FC<{
        children: ReactNode
    }>
    Title: React.FC<{
        children: ReactNode
    }>
    Body: React.FC<{
        children: ReactNode
    }>
    Description: React.FC<{
        children: ReactNode
        bottomMargin?: boolean
    }>
    Actions: React.FC<{
        children: ReactNode
    }>
    Action: React.FC<
        ComponentProps<'button'> & {
            loading?: boolean
        }
    >
}

interface ModalProps {
    open: boolean
    onClose: () => void
    children: ReactNode
}

const Modal: Modal = ({ open, onClose, children }) => {
    return (
        <Drawer open={open} onClose={onClose}>
            {children}
        </Drawer>
    )
}

Modal.Header = styled.div`
    ${tw`shrink-0 p-8 sm:p-6 border-b border-accent-200`}
`

/*
 * Dialog.Title rather than a bare h3: Headless UI points the dialog's
 * `aria-labelledby` at whatever it renders, and without it the panel had no
 * accessible name at all -- a screen reader announced "dialog" and nothing
 * else, even though a visible heading was sitting right there. `as='h3'`
 * keeps the heading level the markup already used.
 */
const StyledTitle = styled(Dialog.Title)`
    ${tw`text-xl font-medium text-foreground text-center`}
`

Modal.Title = ({ children }) => <StyledTitle as='h3'>{children}</StyledTitle>

Modal.Body = styled.div`
    ${tw`min-h-0 flex-1 overflow-y-auto p-6 bg-accent-100`}
`

Modal.Description = ({ children, bottomMargin }) => {
    return (
        <Dialog.Description
            className={`text-accent-600 text-sm ${bottomMargin && 'mb-5'}`}
        >
            {children}
        </Dialog.Description>
    )
}

Modal.Actions = styled.div`
    ${tw`shrink-0 flex border-t border-accent-200`}

    & > button:is(:first-of-type) {
        ${tw`rounded-bl`}
    }

    & > button:not(:last-child) {
        ${tw`border-r border-accent-200`}
    }

    & > button:is(:last-child) {
        ${tw`!text-foreground rounded-br`}
    }
`

const StyledAction = styled.button`
    ${tw`grow py-6 uppercase text-xs text-accent-500 sm:hover:text-foreground active:text-foreground bg-background sm:hover:bg-accent-100 active:bg-accent-100 disabled:bg-accent-100 disabled:cursor-not-allowed transition-colors`}
`

Modal.Action = ({ loading, disabled, children, ...props }) => {
    return (
        <StyledAction {...props} disabled={disabled || loading}>
            {loading ? (
                <div className='grid place-items-center w-full h-full'>
                    <LoadingDots size={4} />
                </div>
            ) : (
                children
            )}
        </StyledAction>
    )
}

export default Modal
