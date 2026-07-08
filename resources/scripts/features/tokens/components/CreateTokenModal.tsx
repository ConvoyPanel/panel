import { IconCopy, IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { toast } from 'sonner'

import { createToken } from '@/features/tokens/api.ts'
import {
    buildAbilities,
    resourceLabels,
    TOKEN_RESOURCES,
    type PaginatedApiKeys,
    type ResourceAccess,
    type TokenResource,
} from '@/features/tokens/types.ts'
import type { Mutator } from '@/types/query.ts'

import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

const emptyScopes = (): Record<TokenResource, ResourceAccess> =>
    Object.fromEntries(TOKEN_RESOURCES.map(r => [r, 'none'])) as Record<
        TokenResource,
        ResourceAccess
    >

interface Props {
    mutate: Mutator<PaginatedApiKeys>
}

const CreateTokenModal = ({ mutate }: Props) => {
    const [open, setOpen] = useState(false)
    const [submitting, setSubmitting] = useState(false)

    const [name, setName] = useState('')
    const [fullAccess, setFullAccess] = useState(true)
    const [scopes, setScopes] = useState(emptyScopes)
    const [plainTextToken, setPlainTextToken] = useState<string | null>(null)

    const abilities = buildAbilities(fullAccess, scopes)
    const canSubmit = name.trim().length > 0 && abilities.length > 0

    const reset = () => {
        setName('')
        setFullAccess(true)
        setScopes(emptyScopes())
        setPlainTextToken(null)
    }

    const onOpenChange = (next: boolean) => {
        if (submitting) return
        setOpen(next)
        if (!next) {
            // Reset after the close animation so the form doesn't flash.
            setTimeout(reset, 200)
        }
    }

    const submit = async () => {
        setSubmitting(true)
        try {
            const token = await createToken(name.trim(), abilities)

            await mutate(data => {
                if (!data) return
                return { ...data, items: [token, ...data.items] }
            }, false)

            setPlainTextToken(token.plainTextToken ?? '')
            toast.success('API token created')
        } catch (e) {
            toast.error('Failed to create token')
            throw e
        } finally {
            setSubmitting(false)
        }
    }

    const copy = async () => {
        if (!plainTextToken) return
        await navigator.clipboard.writeText(plainTextToken)
        toast.success('Copied token to clipboard')
    }

    return (
        <Credenza open={open} onOpenChange={onOpenChange}>
            <CredenzaTrigger asChild>
                <Button className={'flex'} size={'sm'}>
                    <IconPlus className={'mr-2 size-4'} /> Create token
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                {plainTextToken ? (
                    <>
                        <CredenzaHeader>
                            <CredenzaTitle>Token created</CredenzaTitle>
                            <CredenzaDescription>
                                Copy this token now. For security, it won’t be
                                shown again.
                            </CredenzaDescription>
                        </CredenzaHeader>
                        <CredenzaBody>
                            <div
                                className={
                                    'flex items-center gap-2 rounded-md border bg-muted p-3'
                                }
                            >
                                <code
                                    className={
                                        'grow overflow-x-auto whitespace-nowrap font-mono text-sm'
                                    }
                                >
                                    {plainTextToken}
                                </code>
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    onClick={copy}
                                    className={'shrink-0'}
                                >
                                    <IconCopy className={'h-4 w-4'} />
                                </Button>
                            </div>
                        </CredenzaBody>
                        <CredenzaFooter>
                            <CredenzaClose asChild>
                                <Button>Done</Button>
                            </CredenzaClose>
                        </CredenzaFooter>
                    </>
                ) : (
                    <>
                        <CredenzaHeader>
                            <CredenzaTitle>Create API token</CredenzaTitle>
                            <CredenzaDescription>
                                A panel-wide token for the application API.
                            </CredenzaDescription>
                        </CredenzaHeader>
                        <CredenzaBody className={'space-y-4'}>
                            <div className={'space-y-2'}>
                                <Label htmlFor={'token-name'}>Name</Label>
                                <Input
                                    id={'token-name'}
                                    value={name}
                                    onChange={e => setName(e.target.value)}
                                    placeholder={'e.g. Deploy bot'}
                                />
                            </div>

                            <label
                                className={
                                    'flex items-center gap-2 text-sm font-medium'
                                }
                            >
                                <Checkbox
                                    checked={fullAccess}
                                    onCheckedChange={v =>
                                        setFullAccess(v === true)
                                    }
                                />
                                Full access (all resources)
                            </label>

                            {!fullAccess && (
                                <div className={'space-y-2'}>
                                    <Label>Scoped access</Label>
                                    <div className={'space-y-2'}>
                                        {TOKEN_RESOURCES.map(resource => (
                                            <div
                                                key={resource}
                                                className={
                                                    'flex items-center justify-between gap-2'
                                                }
                                            >
                                                <span
                                                    className={'text-sm'}
                                                >
                                                    {resourceLabels[resource]}
                                                </span>
                                                <Select
                                                    value={scopes[resource]}
                                                    onValueChange={(
                                                        v: ResourceAccess
                                                    ) =>
                                                        setScopes(prev => ({
                                                            ...prev,
                                                            [resource]: v,
                                                        }))
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className={'w-44'}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            value={'none'}
                                                        >
                                                            No access
                                                        </SelectItem>
                                                        <SelectItem
                                                            value={'read'}
                                                        >
                                                            Read only
                                                        </SelectItem>
                                                        <SelectItem
                                                            value={'write'}
                                                        >
                                                            Read &amp; write
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <Button
                                onClick={submit}
                                disabled={!canSubmit || submitting}
                            >
                                Create token
                            </Button>
                        </CredenzaFooter>
                    </>
                )}
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateTokenModal
