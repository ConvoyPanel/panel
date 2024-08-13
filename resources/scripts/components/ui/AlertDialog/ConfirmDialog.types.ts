import { type VariantProps } from 'class-variance-authority'

import { buttonVariants } from '@/components/ui/Button'

import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '.'

export interface ConfirmDialogButtonProps
    extends React.ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {}

export type ConfirmOptions = {
    title: React.ReactNode | string
    description?: React.ReactNode | string
    confirmButton?: ConfirmDialogButtonProps
    cancelButton?: ConfirmDialogButtonProps
    confirmText?: string
    cancelText?: string
    icon?: React.ReactNode
    customActions?: (
        onConfirm: () => void,
        onCancel: () => void
    ) => React.ReactNode
    alertDialog?: React.ComponentPropsWithoutRef<typeof AlertDialog>
    alertDialogContent?: React.ComponentPropsWithoutRef<
        typeof AlertDialogContent
    >
    alertDialogHeader?: React.ComponentPropsWithoutRef<typeof AlertDialogHeader>
    alertDialogTitle?: React.ComponentPropsWithoutRef<typeof AlertDialogTitle>
    alertDialogDescription?: React.ComponentPropsWithoutRef<
        typeof AlertDialogDescription
    >
    alertDialogFooter?: React.ComponentPropsWithoutRef<typeof AlertDialogFooter>
}

export type ConfirmationType = (options: ConfirmOptions) => Promise<boolean>

export type ConfirmDialogProps = {
    isOpen: boolean
    onOpenChange: (isOpen: boolean) => void
    config: Partial<ConfirmOptions>
    onConfirm: () => void
    onCancel: () => void
}

export type ConfirmDialogProviderProps = {
    defaultOptions: Partial<ConfirmOptions>
}
