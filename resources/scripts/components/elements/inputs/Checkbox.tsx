import ErrorMessage from '@/components/elements/ErrorMessage'
import { CheckIcon, MinusIcon } from '@heroicons/react/20/solid'
import { ChangeEvent, ReactNode, useMemo } from 'react'

export interface CheckboxProps {
    onChange?: (e: ChangeEvent<HTMLInputElement>) => void
    checked?: boolean
    indeterminate?: boolean
    name?: string
    label?: ReactNode
    error?: ReactNode
    disabled?: boolean
    className?: string
}

const getClasses = (checked?: boolean, indeterminate?: boolean, disabled?: boolean) => {
    if (checked || indeterminate) {
        return disabled
            ? indeterminate
                ? 'border-accent-300 bg-accent-100'
                : 'border-accent-300 bg-accent-300'
            : indeterminate
            ? 'border-foreground'
            : 'border-foreground bg-foreground'
    }

    return disabled
        ? 'border-accent-300 bg-accent-100'
        : 'border-accent-500 hover:border-foreground active:bg-accent-200'
}

const Checkbox = ({ onChange, checked, indeterminate, name, label, error, disabled, className }: CheckboxProps) => {
    const id = useMemo(() => Math.random().toString(36).substring(7), [])

    return (
        <div className={className}>
            <label
                className={`inline-flex items-center space-x-3 ${disabled ? 'cursor-not-allowed' : 'cursor-pointer'}`}
                htmlFor={id}
            >
                <div className='grid place-items-center p-0.5 -m-0.5'>
                    <input
                        id={id}
                        name={name}
                        type='checkbox'
                        className='sr-only'
                        checked={checked}
                        disabled={disabled}
                        onChange={onChange}
                        ref={input => {
                            if (input) {
                                input.indeterminate = Boolean(indeterminate)
                            }
                        }}
                    />
                    <span
                        className={`${getClasses(
                            checked,
                            indeterminate,
                            disabled
                        )} p-0.5 rounded-[3px] border transition-border h-4 w-4`}
                    >
                        {indeterminate ? (
                            <MinusIcon
                                className={`stroke-2 ${
                                    disabled ? 'stroke-accent-300 text-accent-300' : 'stroke-foreground text-foreground'
                                }`}
                            />
                        ) : checked ? (
                            <CheckIcon className='stroke-2 stroke-background text-background' />
                        ) : null}
                    </span>
                </div>
                {label && (
                    <span className={`text-sm select-none ${disabled ? 'text-accent-300' : 'text-foreground'}`}>{label}</span>
                )}
            </label>
            {error && <ErrorMessage>{error}</ErrorMessage>}
        </div>
    )
}

export default Checkbox
