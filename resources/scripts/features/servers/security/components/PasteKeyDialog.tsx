import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { Label } from '@/components/ui/Label'
import { Textarea } from '@/components/ui/Textarea'

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
    /** Receives the non-empty lines pasted; these are added to this server only. */
    onAdd: (keys: string[]) => void
}

const PasteKeyDialog = ({ open, onOpenChange, onAdd }: Props) => {
    const [value, setValue] = useState('')

    const close = (next: boolean) => {
        onOpenChange(next)
        if (!next) setTimeout(() => setValue(''), 200)
    }

    const add = () => {
        const keys = value
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0)

        if (keys.length === 0) return

        onAdd(keys)
        close(false)
    }

    return (
        <Credenza open={open} onOpenChange={close}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Paste a one-off key</CredenzaTitle>
                    <CredenzaDescription>
                        Added to this server only — it isn’t saved to your
                        keychain. One key per line.
                    </CredenzaDescription>
                </CredenzaHeader>
                <CredenzaBody className={'space-y-2'}>
                    <Label htmlFor={'paste-key'}>Public key</Label>
                    <Textarea
                        id={'paste-key'}
                        rows={4}
                        value={value}
                        onChange={e => setValue(e.target.value)}
                        placeholder={'ssh-ed25519 AAAA… user@host'}
                        className={'font-mono text-xs'}
                    />
                </CredenzaBody>
                <CredenzaFooter className={'mt-4'}>
                    <CredenzaClose asChild>
                        <Button variant={'outline'} type={'button'}>
                            Cancel
                        </Button>
                    </CredenzaClose>
                    <Button onClick={add} disabled={value.trim().length === 0}>
                        Add key
                    </Button>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default PasteKeyDialog
