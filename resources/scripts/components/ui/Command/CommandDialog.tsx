import type { DialogProps } from '@radix-ui/react-dialog'

import { Dialog, DialogContent, DialogTitle } from '@/components/ui/Dialog'

import { Command } from '.'

const CommandDialog = ({ children, ...props }: DialogProps) => {
    return (
        <Dialog {...props}>
            <DialogContent className='overflow-hidden p-0'>
                <DialogTitle className='sr-only'>Command menu</DialogTitle>
                <Command>{children}</Command>
            </DialogContent>
        </Dialog>
    )
}

export default CommandDialog
