import { ReactNode, useState } from 'react'
import { useFormState } from 'react-hook-form'

import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'

interface Props {
    /**
     * The RHF names this fold contains, dotted paths included. Drives both
     * auto-open rules, so a field left out of this list can have its error
     * swallowed by the fold.
     */
    fields: string[]
    /**
     * One line naming what is behind the fold, in values rather than field
     * counts — "2 vCPU · 2 GiB · 20 GiB", not "5 settings". Watch the fields so
     * it stays true while they are edited.
     */
    summary: ReactNode
    children: ReactNode
}

/** Walks a dotted RHF path (`disks.0.size`) without pulling in a lodash. */
const at = (source: unknown, path: string): unknown =>
    path
        .split('.')
        .reduce<any>(
            (value, key) => (value == null ? value : value[key]),
            source
        )

/** A field-array's entry is an array, which is only "set" once it has entries. */
const isSet = (value: unknown) =>
    Array.isArray(value) ? value.length > 0 : Boolean(value)

/**
 * A group of fields that states its own values instead of asking for them.
 *
 * Most of the server create form is answered by defaults or by a preset, so
 * printing those answers and offering an Edit reads better than two dozen
 * inputs of equal weight. Nothing is hidden — the values are on screen, they
 * have just stopped being questions.
 *
 * It opens itself in the two cases where a fold would otherwise lie:
 *
 *   - **a field inside it is dirty**, which is exactly what applying a preset
 *     does (`applyPresetSettings` sets `shouldDirty`, and RHF only marks a
 *     field dirty when the new value actually differs from the default) — so a
 *     preset's changes announce themselves rather than hiding behind the fold;
 *   - **a field inside it failed validation**, including the server-side errors
 *     `handleFormErrors` maps back onto the form after a rejected submit.
 *
 * An explicit click always wins over the dirty rule, so a group can be folded
 * away again after editing — but never over the error rule, since hiding the
 * message would leave a submit that fails with nothing on screen to explain it.
 */
const FieldFold = ({ fields, summary, children }: Props) => {
    const { dirtyFields, errors } = useFormState({ name: fields })
    const [openedByHand, setOpenedByHand] = useState<boolean | null>(null)

    const isEdited = fields.some(name => isSet(at(dirtyFields, name)))
    const hasError = fields.some(name => isSet(at(errors, name)))

    const open = hasError || (openedByHand ?? isEdited)

    return (
        <Collapsible open={open} onOpenChange={setOpenedByHand}>
            {/* Dashed rather than solid: this row is a statement about fields
                that are elsewhere, not a field itself, and a solid border made
                it read as another input in the stack. */}
            <div
                className={
                    'flex items-center justify-between gap-3 rounded-lg border border-dashed px-3 py-2'
                }
            >
                <p className={'text-muted-foreground min-w-0 truncate text-sm'}>
                    {summary}
                </p>
                <CollapsibleTrigger className={'w-auto shrink-0'}>
                    {open ? 'Hide' : 'Edit'}
                </CollapsibleTrigger>
            </div>
            <CollapsiblePanel>
                <div className={'pt-3'}>{children}</div>
            </CollapsiblePanel>
        </Collapsible>
    )
}

export default FieldFold
