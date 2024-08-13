import * as React from 'react'

import { Button } from '@/components/ui/Button'

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '.'
import type { ConfirmDialogProps } from './ConfirmDialog.types.ts'

const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
    isOpen,
    onOpenChange,
    config: {
        title,
        description,
        cancelButton,
        confirmButton,
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        icon,
        customActions,
        alertDialog,
        alertDialogContent,
        alertDialogHeader,
        alertDialogTitle,
        alertDialogDescription,
        alertDialogFooter,
    },
    onConfirm,
    onCancel,
}) => {
    return (
        <AlertDialog open={isOpen} onOpenChange={onOpenChange} {...alertDialog}>
            <AlertDialogContent {...alertDialogContent}>
                <AlertDialogHeader {...alertDialogHeader}>
                    <AlertDialogTitle {...alertDialogTitle}>
                        {icon && icon}
                        {title}
                    </AlertDialogTitle>

                    <AlertDialogDescription {...alertDialogDescription}>
                        {description && description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter {...alertDialogFooter}>
                    {customActions ? (
                        customActions(onConfirm, onCancel)
                    ) : (
                        <>
                            <AlertDialogCancel asChild>
                                <Button
                                    variant={'outline'}
                                    onClick={onCancel}
                                    {...cancelButton}
                                >
                                    {cancelText}
                                </Button>
                            </AlertDialogCancel>
                            <AlertDialogAction asChild>
                                <Button onClick={onConfirm} {...confirmButton}>
                                    {confirmText}
                                </Button>
                            </AlertDialogAction>
                        </>
                    )}
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    )
}

export default ConfirmDialog
